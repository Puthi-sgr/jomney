<?php
namespace App\Core;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class CloudinaryService{
    private $uploadApi;

    public function __construct()
    {
        //Get all the credentials from the .env
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
                'api_key'    => $_ENV['CLOUDINARY_API_KEY'],
                'api_secret' => $_ENV['CLOUDINARY_API_SECRET'],
            ]
        ]);
        
        $this->uploadApi = new UploadApi();
    }

    public function uploadImage($filePath, $folder = 'food_delivery'): ?string{

        try{
            //The file path is the image path
            $result = $this->uploadApi->upload($filePath, [
                'folder' => $folder,
                'transformation' => [
                    'width' => 800,
                    'height' => 600,
                    'crop' => 'fill'
                ]
            ]);

            return $result['secure_url'];

        }catch(\Exception $e){
            error_log("Cloudinary upload error: ", $e->getMessage());
            return null;
        }
    }
}