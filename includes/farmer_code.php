<?php
// includes/farmer_code.php
function generateFarmerCode($stateCode, $districtCode, $conn) {
    // Format: AS-01-RC-0001
    // AS = State Code, 01 = District Sequence, RC = Registration Code (fixed), 0001 = Farmer Sequence
    
    // Get or create district sequence
    $query = "SELECT sequence_number FROM farmer_sequences 
              WHERE state_code = :state_code AND district_code = :district_code 
              FOR UPDATE";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':state_code', $stateCode);
    $stmt->bindParam(':district_code', $districtCode);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
        $sequenceNumber = $sequence['sequence_number'] + 1;
        
        // Update sequence
        $updateQuery = "UPDATE farmer_sequences SET sequence_number = :sequence_number, 
                       last_used = NOW() 
                       WHERE state_code = :state_code AND district_code = :district_code";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':sequence_number', $sequenceNumber);
        $updateStmt->bindParam(':state_code', $stateCode);
        $updateStmt->bindParam(':district_code', $districtCode);
        $updateStmt->execute();
    } else {
        // Create new sequence
        $sequenceNumber = 1;
        $insertQuery = "INSERT INTO farmer_sequences (state_code, district_code, sequence_number) 
                       VALUES (:state_code, :district_code, :sequence_number)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindParam(':state_code', $stateCode);
        $insertStmt->bindParam(':district_code', $districtCode);
        $insertStmt->bindParam(':sequence_number', $sequenceNumber);
        $insertStmt->execute();
    }
    
    // Get district sequence number (padded to 2 digits)
    $districtSeqQuery = "SELECT COUNT(*) as count FROM districts 
                        WHERE state_id = (SELECT id FROM states WHERE state_code = :state_code)";
    $districtSeqStmt = $conn->prepare($districtSeqQuery);
    $districtSeqStmt->bindParam(':state_code', $stateCode);
    $districtSeqStmt->execute();
    $districtCount = $districtSeqStmt->fetch(PDO::FETCH_ASSOC)['count'];
    $districtSequence = str_pad($districtCount, 2, '0', STR_PAD_LEFT);
    
    // Format the farmer code - Use "RC" (Registration Code) instead of district code
    $farmerSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    $farmerCode = $stateCode . '-' . $districtSequence . '-RC-' . $farmerSequence;
    
    return $farmerCode;
}

// Function to get states for dropdown
function getStates($conn) {
    $query = "SELECT * FROM states WHERE is_active = 1 ORDER BY state_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get districts for a state
function getDistrictsByState($stateId, $conn) {
    $query = "SELECT * FROM districts WHERE state_id = :state_id AND is_active = 1 ORDER BY district_name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':state_id', $stateId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>