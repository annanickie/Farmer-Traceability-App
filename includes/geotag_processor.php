<?php
// includes/geotag_processor.php

class GeotagProcessor {
    private $upload_base_path;
    
    public function __construct() {
        $this->upload_base_path = defined('UPLOAD_BASE_PATH') ? UPLOAD_BASE_PATH : dirname(__DIR__) . '/uploads/';
    }
    
    /**
     * Process single geotagged photo
     */
    public function processGeotaggedPhoto($file, $geotag, $type) {
        $result = [
            'success' => false,
            'file_path' => null,
            'geotag' => $geotag,
            'error' => null
        ];
        
        try {
            // Validate file
            $validation = validateFileUpload($file, ALLOWED_IMAGE_TYPES, 5 * 1024 * 1024);
            if (!$validation['success']) {
                throw new Exception($validation['error']);
            }
            
            // Generate unique filename
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $type . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $this->getUploadPath($type);
            $file_path = $upload_path . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $result['success'] = true;
                $result['file_path'] = $file_path;
                
                // Add geotag to image EXIF data if possible
                if ($geotag && $file_extension !== 'png') {
                    $this->addGeotagToImage($file_path, $geotag);
                }
            } else {
                throw new Exception('Failed to move uploaded file.');
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            error_log('GeotagProcessor Error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Process multiple geotagged photos
     */
    public function processMultipleGeotaggedPhotos($files, $geotags, $type) {
        $result = [
            'success' => false,
            'photos' => [],
            'geotags' => [],
            'error' => null
        ];
        
        try {
            $geotag_array = $geotags ? explode('|', $geotags) : [];
            
            // Handle multiple files
            if (is_array($files['name'])) {
                foreach ($files['name'] as $index => $filename) {
                    if (!empty($filename)) {
                        $file_data = [
                            'name' => $files['name'][$index],
                            'type' => $files['type'][$index],
                            'tmp_name' => $files['tmp_name'][$index],
                            'error' => $files['error'][$index],
                            'size' => $files['size'][$index]
                        ];
                        
                        $geotag = $geotag_array[$index] ?? null;
                        $photo_result = $this->processGeotaggedPhoto($file_data, $geotag, $type);
                        
                        if ($photo_result['success']) {
                            $result['photos'][] = [
                                'file_path' => $photo_result['file_path'],
                                'geotag' => $photo_result['geotag'],
                                'timestamp' => date('Y-m-d H:i:s')
                            ];
                            $result['geotags'][] = $photo_result['geotag'];
                        }
                    }
                }
            } else {
                // Single file
                $geotag = $geotag_array[0] ?? null;
                $photo_result = $this->processGeotaggedPhoto($files, $geotag, $type);
                
                if ($photo_result['success']) {
                    $result['photos'][] = [
                        'file_path' => $photo_result['file_path'],
                        'geotag' => $photo_result['geotag'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $result['geotags'][] = $photo_result['geotag'];
                }
            }
            
            $result['success'] = !empty($result['photos']);
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            error_log('GeotagProcessor Multiple Photos Error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Process base64 image data (from camera capture)
     */
    public function processBase64Image($base64_data, $geotag, $type) {
        $result = [
            'success' => false,
            'file_path' => null,
            'geotag' => $geotag,
            'error' => null
        ];
        
        try {
            // Extract base64 data
            if (preg_match('/^data:image\/(\w+);base64,/', $base64_data, $matches)) {
                $image_type = $matches[1];
                $base64_data = substr($base64_data, strpos($base64_data, ',') + 1);
            } else {
                $image_type = 'jpeg'; // default
            }
            
            // Validate image type
            if (!in_array($image_type, ['jpeg', 'jpg', 'png'])) {
                throw new Exception('Invalid image type. Only JPEG and PNG are allowed.');
            }
            
            // Decode base64 data
            $image_data = base64_decode($base64_data);
            if (!$image_data) {
                throw new Exception('Invalid base64 image data.');
            }
            
            // Generate unique filename
            $filename = $type . '_' . time() . '_' . uniqid() . '.' . $image_type;
            $upload_path = $this->getUploadPath($type);
            $file_path = $upload_path . $filename;
            
            // Save image file
            if (file_put_contents($file_path, $image_data)) {
                $result['success'] = true;
                $result['file_path'] = $file_path;
                
                // Add geotag to image EXIF data if possible
                if ($geotag && $image_type !== 'png') {
                    $this->addGeotagToImage($file_path, $geotag);
                }
            } else {
                throw new Exception('Failed to save image file.');
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            error_log('GeotagProcessor Base64 Error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Get upload path for file type
     */
    private function getUploadPath($type) {
        $path = $this->upload_base_path . $type . '_photos/';
        
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        
        return $path;
    }
    
    /**
     * Add geotag to image EXIF data
     */
    private function addGeotagToImage($file_path, $geotag) {
        // Parse geotag coordinates
        $coordinates = $this->parseGeotag($geotag);
        if (!$coordinates) {
            return false;
        }
        
        list($latitude, $longitude) = $coordinates;
        
        // Check if EXIF extension is available
        if (!function_exists('exif_read_data')) {
            error_log("EXIF extension not available for geotagging: {$file_path}");
            return false;
        }
        
        try {
            // This is a simplified implementation
            // In production, you might want to use a proper EXIF writing library
            // For now, we'll log the geotag information and you can implement
            // proper EXIF writing based on your server capabilities
            
            error_log("Geotag for {$file_path}: {$latitude}, {$longitude}");
            
            // Example of how you might implement EXIF writing with a library:
            // $image = new PelJpeg($file_path);
            // $exif = new PelExif();
            // $gps = new PelTag(GPS_LATITUDE_REF);
            // ... etc.
            
            return true;
            
        } catch (Exception $e) {
            error_log("Geotag EXIF Error for {$file_path}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse geotag string into coordinates
     */
    public function parseGeotag($geotag) {
        if (empty($geotag)) {
            return null;
        }
        
        $parts = explode(',', $geotag);
        if (count($parts) !== 2) {
            return null;
        }
        
        $lat = trim($parts[0]);
        $lng = trim($parts[1]);
        
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        
        $lat = (float)$lat;
        $lng = (float)$lng;
        
        // Validate coordinate ranges
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }
        
        return [$lat, $lng];
    }
    
    /**
     * Validate geolocation coordinates
     */
    public function validateGeolocation($latitude, $longitude, $accuracy = null) {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return false;
        }
        
        $lat = (float)$latitude;
        $lng = (float)$longitude;
        
        // Check coordinate ranges
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }
        
        // Check accuracy if provided
        if ($accuracy !== null && $accuracy > MAX_GEOTAG_ACCURACY) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate distance between two coordinates in meters
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
}
?>