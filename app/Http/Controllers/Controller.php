<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DB;
use Session;
use App;

class Controller extends BaseController
{

    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;

    public function encpassword($passwordPlain = 0)
    {
        return password_hash($passwordPlain, PASSWORD_DEFAULT);
    }

    public function getRandString($length)
    {
        $length = ceil($length / 2);
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    public static function getRoles($id = null, $roleId = null)
    {
        global $roles;
        if ($id != 1) {
            $adminInfo = DB::table('admins')->select('admins.role_ids', 'admins.id')->where('id', $id)->first();
            $role_ids = explode(',', $adminInfo->role_ids);
            if (in_array($roleId, $role_ids)) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    public function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function serialiseFormData($data = array(), $isEdit = 0)
    {
        $formData = array();
        unset($data['_token']);
        unset($data['_method']);
        unset($data['confirm_password']);
        if ($isEdit == 0) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    public function createSlug($slug = null, $tablename = null, $fieldname = 'slug')
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);
        $slug = str_replace(' ', '-', strtolower($slug));
        $isSlugExist = DB::table($tablename)->where($fieldname, $slug)->first();
        if (!empty($isSlugExist)) {
            $slug = $slug . '-' . bin2hex(openssl_random_pseudo_bytes(6));
            $this->createSlug($slug, $tablename, $fieldname);
        }
        return $slug;
    }

    /* public function uploadImage($file, $upload_path = null)
    {
        $orgName = $file->getClientOriginalName();
        $orgName = str_replace(' ', '_', $orgName);
        $newFileName = bin2hex(openssl_random_pseudo_bytes(4)) . '_' . $orgName;
        $file->move($upload_path, $newFileName);
        return $newFileName;
    } */
    public function uploadImage($file, $upload_path = null)
    {
        $extension = $file->getClientOriginalExtension();
        // sanitize filename
        $orgName = preg_replace('/[^A-Za-z0-9\-\_\.]/', '', $file->getClientOriginalName());
        $newFileName = bin2hex(random_bytes(8)) . '.' . $extension;
        $file->move($upload_path, $newFileName);
        return $newFileName;
    }


    public function resizeImage($uploadedFileName, $imgFolder, $thumbfolder, $newWidth = false, $newHeight = false, $quality = 75, $bgcolor = false)
    {
        $img = $imgFolder . $uploadedFileName;
        $newName = $uploadedFileName;
        $dest = $thumbfolder . $newName;
        list($oldWidth, $oldHeight, $type) = getimagesize($img);
        $ext = $this->image_type_to_extension($type);
        if ($newWidth OR $newHeight) {
            $widthScale = 2;
            $heightScale = 2;

            if ($newWidth)
                $widthScale = $newWidth / $oldWidth;
            if ($newHeight)
                $heightScale = $newHeight / $oldHeight;
            //debug("W: $widthScale  H: $heightScale<br>");
            if ($widthScale < $heightScale) {
                $maxWidth = $newWidth;
                $maxHeight = false;
            } elseif ($widthScale > $heightScale) {
                $maxHeight = $newHeight;
                $maxWidth = false;
            } else {
                $maxHeight = $newHeight;
                $maxWidth = $newWidth;
            }

            if ($maxWidth > $maxHeight) {
                $applyWidth = $maxWidth;
                $applyHeight = ($oldHeight * $applyWidth) / $oldWidth;
            } elseif ($maxHeight > $maxWidth) {
                $applyHeight = $maxHeight;
                $applyWidth = ($applyHeight * $oldWidth) / $oldHeight;
            } else {
                $applyWidth = $maxWidth;
                $applyHeight = $maxHeight;
            }

            $startX = 0;
            $startY = 0;

            switch ($ext) {
                case 'gif':
                    $oldImage = imagecreatefromgif($img);
                    break;
                case 'png':
                    $oldImage = imagecreatefrompng($img);
                    break;
                case 'jpg':
                case 'jpeg':
                    $oldImage = imagecreatefromjpeg($img);
                    break;
                default:
                    return false;
                    break;
            }
            //create new image
            $newImage = imagecreatetruecolor($applyWidth, $applyHeight);
            imagecopyresampled($newImage, $oldImage, 0, 0, $startX, $startY, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
            switch ($ext) {
                case 'gif':
                    imagegif($newImage, $dest, $quality);
                    break;
                case 'png':
                    imagepng($newImage, $dest, 8);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($newImage, $dest, $quality);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($newImage);
            imagedestroy($oldImage);
            if (!$newName) {
                unlink($img);
                rename($dest, $img);
            }
            return true;
        }
    }

    public function image_type_to_extension($imagetype)
    {
        if (empty($imagetype))
            return false;
        switch ($imagetype) {
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_JPEG:
                return 'jpg';
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_SWF:
                return 'swf';
            case IMAGETYPE_PSD:
                return 'psd';
            case IMAGETYPE_BMP:
                return 'bmp';
            case IMAGETYPE_TIFF_II:
                return 'tiff';
            case IMAGETYPE_TIFF_MM:
                return 'tiff';
            case IMAGETYPE_JPC:
                return 'jpc';
            case IMAGETYPE_JP2:
                return 'jp2';
            case IMAGETYPE_JPX:
                return 'jpf';
            case IMAGETYPE_JB2:
                return 'jb2';
            case IMAGETYPE_SWC:
                return 'swc';
            case IMAGETYPE_IFF:
                return 'aiff';
            case IMAGETYPE_WBMP:
                return 'wbmp';
            case IMAGETYPE_XBM:
                return 'xbm';
            default:
                return false;
        }
    }

    public function numberFormatPrecision($number, $precision = 2, $separator = '.')
    {
        $numberParts = explode($separator, $number);
        $response = $numberParts[0];
        if (count($numberParts) > 1 && $precision > 0) {
            $response .= $separator;
            $response .= substr($numberParts[1], 0, $precision);
        }
        return $response;
    }

    public function validatePermission($role_id, $permission)
    {
        $flag = DB::table('permissions')->select('permissions.id')->where('role_id', $role_id)->where('permission_name', $permission)->first();
        if (!empty($flag))
            return true;
        else
            return false;
    }

    public function getSession()
    {

        if (Session::get('locale')) {
            $locale = Session::get('locale');
        } else {
            $locale = 'ku';
            $_SESSION['Config']['language'] = $locale;
            Session::put('locale', $locale);
        }

        App::setLocale($locale);
    }
    public function roundAmount($amount)
    {
        $amount = abs((float) $amount);
        return ($amount - floor($amount)) > 0.5 ? ceil($amount) : floor($amount);
    }

    public function numberFormatSpaces($amount)
    {
        return number_format($amount, 0, '', '');
    }

}
