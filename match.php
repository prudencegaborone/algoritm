<?php
// Include required files for database configuration and models
require_once __DIR__ . '/../config/db.php';       // Database configuration
require_once __DIR__ . '/../models/Student.php';   // Student model
require_once __DIR__ . '/../models/Organization.php'; // Organization model

/**
 * MatchingController class handles the logic for matching students with organizations
 */
class MatchingController {
    // Class properties (variables available throughout the class)
    private $db;            // Database connection object
    private $studentModel;   // Student model instance
    private $orgModel;      // Organization model instance

    /**
     * Constructor method - runs when a new MatchingController is created
     * Initializes database connection and models
     */
    public function __construct() {
        // Create new Database instance
        $database = new Database();
        
        // Get database connection and store in $this->db property
        // The -> operator accesses properties/methods of an object
        $this->db = $database->getConnection();
        
        // Initialize Student model with database connection
        $this->studentModel = new Student($this->db);
        
        // Initialize Organization model with database connection
        $this->orgModel = new Organization($this->db);
    }

    /**
     * Main matching function that pairs students with organizations
     * @return array Array of matches with student IDs, org IDs, and match scores
     */
    public function matchStudentsToOrganizations() {
        // Get all students who haven't been matched yet
        // Calls getUnmatchedStudents() method on studentModel object
        $students = $this->studentModel->getUnmatchedStudents();
        
        // Get all available positions from organizations
        $positions = $this->orgModel->getAvailablePositions();
        
        // Initialize empty array to store matches
        $matches = [];
        
        // Loop through each student and each position to find potential matches
        foreach($students as $student) {
            foreach($positions as $position) {
                // Calculate match score between this student and position
                $matchScore = $this->calculateMatchScore($student, $position);
                
                // Only consider matches with score 50% or higher
                if($matchScore >= 50) {
                    // Add match to matches array
                    $matches[] = [
                        'student_id' => $student['student_id'],  // Student ID from student array
                        'org_id' => $position['org_id'],        // Organization ID from position array
                        'position_id' => $position['id'],       // Position ID from position array
                        'match_score' => $matchScore            // Calculated match score
                    ];
                }
            }
        }
        
        // Sort matches by score in descending order (highest first)
        // usort() sorts an array using a user-defined comparison function
        // The <=> is the "spaceship operator" that compares two values:
        // Returns -1 if left is less than right, 0 if equal, 1 if greater
        usort($matches, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        // Return the sorted matches array
        return $matches;
    }
    
    /**
     * Calculates a match score between a student and a position
     * @param array $student Student data array
     * @param array $position Position data array
     * @return float Match score (0-100)
     */
    private function calculateMatchScore($student, $position) {
        $totalScore = 0; // Initialize total score
        
        // 1. Skill Matching (worth up to 50% of total score)
        $skillScore = $this->calculateSkillMatch($student['student_id'], $position['org_id']);
        $totalScore += $skillScore * 0.5; // Add 50% of skill score to total
        
        // 2. Location Preference (worth up to 20%)
        // Get student preferences from database
        $studentPref = $this->studentModel->getPreferences($student['student_id']);
        $locationScore = 0;
        
        // Check if location preference matches
        if(!empty($studentPref['preferred_location']) && 
           !empty($position['location']) &&
           strtolower($studentPref['preferred_location']) == strtolower($position['location'])) {
            $locationScore = 20; // Full points for matching location
        }
        $totalScore += $locationScore;
        
        // 3. Industry Preference (worth up to 15%)
        $industryScore = 0;
        if(!empty($studentPref['preferred_industry']) && 
           !empty($position['industry']) &&
           strtolower($studentPref['preferred_industry']) == strtolower($position['industry'])) {
            $industryScore = 15; // Full points for matching industry
        }
        $totalScore += $industryScore;
        
        // 4. Stipend Expectation (worth up to 10%)
        $stipendScore = 0;
        if(!empty($studentPref['min_stipend']) && 
           !empty($position['stipend']) &&
           $position['stipend'] >= $studentPref['min_stipend']) {
            $stipendScore = 10; // Full points if stipend meets expectation
        }
        $totalScore += $stipendScore;
        
        // 5. Academic Performance (worth up to 5%)
        $gpaScore = 0;
        if(!empty($student['gpa'])) {
            // Convert GPA to score (4.0 scale becomes 5% max)
            $gpaScore = min(5, $student['gpa'] * 1.25);
        }
        $totalScore += $gpaScore;
        
        // Ensure total score doesn't exceed 100%
        return min(100, $totalScore);
    }
    
    /**
     * Calculates skill match score between student and organization
     * @param int $studentId Student ID
     * @param int $orgId Organization ID
     * @return float Skill match score (0-100)
     */
    private function calculateSkillMatch($studentId, $orgId) {
        // Get all skills for this student
        $studentSkills = $this->studentModel->getSkills($studentId);
        
        // Get all skill requirements for this organization
        $orgRequirements = $this->orgModel->getRequirements($orgId);
        
        $skillScore = 0;       // Actual score based on matches
        $maxPossibleScore = 0; // Maximum possible score if all requirements perfectly matched
        
        // Loop through each organization requirement
        foreach($orgRequirements as $req) {
            // Calculate maximum possible score for this requirement
            // Each requirement can contribute up to 10 points * its weight
            $maxPossibleScore += $req['weight'] * 10;
            
            // Check if student has this skill
            foreach($studentSkills as $skill) {
                // Compare skills case-insensitively
                if(strtolower($skill['skill']) == strtolower($req['required_skill'])) {
                    // Calculate how well student's proficiency matches requirement
                    $proficiencyScore = $this->getProficiencyScore($skill['proficiency'], $req['min_proficiency']);
                    
                    // Add to total score (weighted by requirement importance)
                    $skillScore += $proficiencyScore * $req['weight'];
                    break; // Move to next requirement after finding match
                }
            }
        }
        
        // Calculate percentage score (actual/max possible)
        if($maxPossibleScore > 0) {
            return ($skillScore / $maxPossibleScore) * 100;
        }
        
        return 0; // No requirements = 0% score
    }
    
    /**
     * Calculates proficiency match score between student and requirement
     * @param string $studentProf Student's proficiency level
     * @param string $requiredProf Required proficiency level
     * @return int Score (1-10)
     */
    private function getProficiencyScore($studentProf, $requiredProf) {
        // Define proficiency levels as numeric values
        $proficiencyLevels = [
            'beginner' => 1,      // Level 1
            'intermediate' => 2,  // Level 2
            'advanced' => 3       // Level 3
        ];
        
        // Get numeric level for student's proficiency (default 0 if not found)
        $studentLevel = $proficiencyLevels[strtolower($studentProf)] ?? 0;
        
        // Get numeric level for required proficiency
        $requiredLevel = $proficiencyLevels[strtolower($requiredProf)] ?? 0;
        
        // Check if student meets or exceeds requirement
        if($studentLevel >= $requiredLevel) {
            return 10; // Full points
        } else {
            // Partial credit based on student level (3 or 6 points)
            return max(1, $studentLevel * 3);
        }
    }
    
    /**
     * Saves matches to database
     * @param array $matches Array of matches to save
     * @return int Number of successfully saved matches
     */
    public function saveMatches($matches) {
        // SQL query to insert matches
        $query = "INSERT INTO matches 
                  (student_id, org_id, position_id, match_score, status) 
                  VALUES (:student_id, :org_id, :position_id, :match_score, 'pending')";
        
        // Prepare the SQL statement
        $stmt = $this->db->prepare($query);
        $successCount = 0; // Track successful saves
        
        // Loop through each match and insert into database
        foreach($matches as $match) {
            // Bind parameters to prevent SQL injection
            // The -> operator here calls the bindParam method on the statement object
            $stmt->bindParam(':student_id', $match['student_id']);
            $stmt->bindParam(':org_id', $match['org_id']);
            $stmt->bindParam(':position_id', $match['position_id']);
            $stmt->bindParam(':match_score', $match['match_score']);
            
            // Execute the statement and count if successful
            if($stmt->execute()) {
                $successCount++;
            }
        }
        
        // Return count of successful inserts
        return $successCount;
    }
}
?>