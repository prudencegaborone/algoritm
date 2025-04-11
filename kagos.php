/**
 * Student Model with GPA Eligibility Check
 */
class Student {
    private $conn;
    private $table = 'students';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Check if student meets basic attachment eligibility requirements
     * @param int $studentId
     * @param float $minGPA (optional) Minimum GPA required by organization
     * @return bool
     */
    public function isEligibleForAttachment($studentId, $minGPA = 2.0) {
        $student = $this->getStudent($studentId);
        
        // Basic university requirements
        $meetsUniversityRequirements = ($student['credits_completed'] >= 60);
        
        // Organization-specific GPA requirement
        $meetsGPARequirement = ($student['gpa'] >= $minGPA);
        
        return ($meetsUniversityRequirements && $meetsGPARequirement);
    }

    /**
     * Get student by ID
     */
    private function getStudent($studentId) {
        $query = "SELECT * FROM " . $this->table . " WHERE student_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

/**
 * Matching Controller with GPA Filter
 */
class MatchingController {
    // ... (other properties and methods)

    /**
     * Get matches with GPA pre-filter
     */
    public function getMatchesWithGPAFilter($orgId) {
        // Get organization requirements including minimum GPA
        $org = $this->orgModel->getOrganization($orgId);
        $minGPA = $org['minimum_gpa'] ?? 2.0; // Default to 2.0 if not specified
        
        // Get all students who meet basic eligibility
        $eligibleStudents = array_filter(
            $this->studentModel->getAllStudents(),
            function($student) use ($minGPA) {
                return $this->studentModel->isEligibleForAttachment(
                    $student['student_id'], 
                    $minGPA
                );
            }
        );
        
        // Now match these pre-filtered students
        return $this->matchStudentsToPositions($eligibleStudents, $orgId);
    }

    /**
     * Match pre-filtered students to positions
     */
    private function matchStudentsToPositions($students, $orgId) {
        $positions = $this->orgModel->getAvailablePositions($orgId);
        $matches = [];
        
        foreach($students as $student) {
            foreach($positions as $position) {
                $matchScore = $this->calculateMatchScore($student, $position);
                
                if($matchScore >= 50) {
                    $matches[] = [
                        'student_id' => $student['student_id'],
                        'position_id' => $position['id'],
                        'match_score' => $matchScore,
                        'gpa' => $student['gpa'] // Include GPA in results
                    ];
                }
            }
        }
        
        usort($matches, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return $matches;
    }
}