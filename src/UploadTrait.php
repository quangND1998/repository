<?php
namespace Darkness\Repository;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

trait UploadTrait
{
    /**
     * upload image
     * @param  mixed $file
     * @return array
     */
    public function upload(array $data)
    {
        $file          = $data['file'];
        $width         = $data['width'] ?? 0;
        $height        = $data['height'] ?? 0;

        $imageName = $this->generateNewFileName($file);

        try {
            if ($file->getClientOriginalExtension() == 'svg') {
                $file->move(storage_path($this->model->uploadPath), $imageName);
                return $this->uploadSuccess($imageName);
            }

            if ($width && $height) {
                Image::make($file->getRealPath())->fit($width, $height)->save($this->getUploadImagePath($imageName));
            } else {
                Image::make($file->getRealPath())->save($this->getUploadImagePath($imageName));
            }
            return $this->uploadSuccess($imageName);
        } catch (\Exception $e) {
            return $this->uploadFail($e);
        } catch (\Throwable $t) {
            return $this->uploadFail($t);
        }
    }

    public function generateNewFileName($file)
    {
        $strSecret   = '!@#$%^&*()_+QBGFTNKU' . time() . rand(111111, 999999);
        $filenameMd5 = md5($file . $strSecret);
        return date('Y_m_d') . '_' . $filenameMd5 . '.' . $file->getClientOriginalExtension();
    }

    /**
     * get image path
     * @param  String $img
     * @return String
     */
    public function getImagePath($img)
    {
        return app('url')->asset($this->model->imgPath . '/' . $img);
    }

    /**
     * get path upload image
     * @param string $img
     * @return string
     */
    public function getUploadImagePath($img)
    {
        if (!File::isDirectory(storage_path($this->model->uploadPath))) {
            File::makeDirectory(storage_path($this->model->uploadPath), 0777, true, true);
        }
        return storage_path($this->model->uploadPath . '/' . $img);
    }

    /**
     * upload success response
     * @param  mixed $data
     * @return array
     */
    protected function uploadSuccess($name)
    {
        return [
            'code'    => 1,
            'message' => 'success',
            'data'    => [
                'name' => $name,
                'path' => $this->getImagePath($name)
            ]
        ];
    }

    /**
    * upload fail response
    * @param  mixed $data
    * @return array
    */
    protected function uploadFail($e)
    {
        return [
            'code'    => 0,
            'message' => 'fail',
            'data'    => $e->getMessage()
        ];
    }

    /**
     * [removeImage description]
     * @param  [type] $image [description]
     * @return [type]        [description]
     */
    public function removeImage($image)
    {
        @unlink($this->getUploadImagePath($image));
    }
}
