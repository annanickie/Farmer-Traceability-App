<?php
// includes/farmer_code.php

function generateFarmerCode($stateCode, $districtCode, $conn) {
    try {
        // Check if farmer_sequences table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'farmer_sequences'");
        
        if ($tableCheck->rowCount() > 0) {
            // Use sequence table method
            return generateFarmerCodeWithSequence($stateCode, $districtCode, $conn);
        } else {
            // Use count-based method (fallback)
            return generateFarmerCodeWithCount($stateCode, $districtCode, $conn);
        }
        
    } catch (Exception $e) {
        error_log("Farmer code generation error: " . $e->getMessage());
        // Fallback to simple method
        return generateSimpleFarmerCode($stateCode, $districtCode, $conn);
    }
}

function generateFarmerCodeWithSequence($stateCode, $districtCode, $conn) {
    // Use the ACTUAL district code, not the count
    $districtSequence = $districtCode; // Use the actual district code
    
    // Get or create farmer sequence for this state-district combination
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
    
    // Format the farmer code: STATE-DISTRICT-RC-SEQUENCE
    $farmerSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    $farmerCode = $stateCode . '-' . $districtSequence . '-RC-' . $farmerSequence;
    
    return $farmerCode;
}

function generateFarmerCodeWithCount($stateCode, $districtCode, $conn) {
    // Use the ACTUAL district code
    $districtSequence = $districtCode;
    
    // Count existing farmers for this specific state-district combination
    $countQuery = "SELECT COUNT(*) as farmer_count FROM farmers 
                   WHERE state = (SELECT id FROM states WHERE state_code = :state_code)
                   AND district = (SELECT id FROM districts WHERE district_code = :district_code)";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindParam(':state_code', $stateCode);
    $countStmt->bindParam(':district_code', $districtCode);
    $countStmt->execute();
    $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    $sequenceNumber = ($countData['farmer_count'] ?? 0) + 1;
    
    // Format the farmer code: STATE-DISTRICT-RC-SEQUENCE
    $farmerSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    $farmerCode = $stateCode . '-' . $districtSequence . '-RC-' . $farmerSequence;
    
    return $farmerCode;
}

function generateSimpleFarmerCode($stateCode, $districtCode, $conn) {
    // Simple format: STATE-DISTRICT-SEQUENCE
    $countQuery = "SELECT COUNT(*) as total_count FROM farmers 
                   WHERE state = (SELECT id FROM states WHERE state_code = :state_code)
                   AND district = (SELECT id FROM districts WHERE district_code = :district_code)";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindParam(':state_code', $stateCode);
    $countStmt->bindParam(':district_code', $districtCode);
    $countStmt->execute();
    $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    $sequenceNumber = ($countData['total_count'] ?? 0) + 1;
    $farmerSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    
    // Simple format: STATE-DISTRICT-SEQUENCE
    $farmerCode = $stateCode . '-' . $districtCode . '-' . $farmerSequence;
    
    return $farmerCode;
}

// Alternative: If you want the format STATE-DISTRICT_NUMBER-RC-SEQUENCE
function generateFarmerCodeAlternative($stateCode, $districtCode, $conn) {
    try {
        // Get district sequence number (district's position in the state)
        $districtSeqQuery = "SELECT d.district_code, 
                            (SELECT COUNT(*) FROM districts d2 
                             WHERE d2.state_id = d.state_id AND d2.id <= d.id) as district_sequence
                            FROM districts d 
                            WHERE d.district_code = :district_code 
                            AND d.state_id = (SELECT id FROM states WHERE state_code = :state_code)";
        $districtSeqStmt = $conn->prepare($districtSeqQuery);
        $districtSeqStmt->bindParam(':state_code', $stateCode);
        $districtSeqStmt->bindParam(':district_code', $districtCode);
        $districtSeqStmt->execute();
        $districtData = $districtSeqStmt->fetch(PDO::FETCH_ASSOC);
        
        $districtSequence = $districtData ? str_pad($districtData['district_sequence'], 2, '0', STR_PAD_LEFT) : '01';
        
        // Get farmer sequence
        $countQuery = "SELECT COUNT(*) as farmer_count FROM farmers 
                       WHERE state = (SELECT id FROM states WHERE state_code = :state_code)
                       AND district = (SELECT id FROM districts WHERE district_code = :district_code)";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bindParam(':state_code', $stateCode);
        $countStmt->bindParam(':district_code', $districtCode);
        $countStmt->execute();
        $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        $sequenceNumber = ($countData['farmer_count'] ?? 0) + 1;
        $farmerSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
        
        // Format: STATE-DISTRICT_SEQUENCE-RC-FARMER_SEQUENCE
        $farmerCode = $stateCode . '-' . $districtSequence . '-RC-' . $farmerSequence;
        
        return $farmerCode;
        
    } catch (Exception $e) {
        error_log("Alternative farmer code generation error: " . $e->getMessage());
        return generateSimpleFarmerCode($stateCode, $districtCode, $conn);
    }
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