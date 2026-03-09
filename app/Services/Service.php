<?php

namespace App\Services;

use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str; 
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class Service
{
    public $model;

    /**
     * Validation helper
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @return array
     */
    public function validate($data, $rules, $messages = [])
    {
        $validation = Validator::make($data, $rules, $messages);

        if ($validation->fails()) {
            return [
                'status' => false,
                'data' => $validation->errors(),
            ];
        }

        return [
            'status' => true,
            'data' => [],
        ];
    }

    /**
     * Check if value exists in the array
     */
    public function checkIfExists(array $haystack, mixed $needle): bool
    {
        return (
            isset($haystack[$needle])
            && $haystack[$needle] !== ''
            && $haystack[$needle] !== null
        )
            ? true
            : false;
    }

    public function dateGenerate($date = 'today', $format = 'Y-m-d')
    {
        if ($date === null) return null;

        return date($format, strtotime($date));
    }

    public function generateUDID()
    {
        return (string) Str::uuid();
    }

    private function makeString($val, $length)
    {
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $val[random_int(0, strlen($val) - 1)];
        }

        return $string;
    }

    public function uploadFile(?UploadedFile $file, string $folderName)
    {
        $path = '';

        if ($file === null) {
            return null;
        }

        $folder = "storage/$folderName";

        $folderPath = public_path($folder);

        if (! File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0777, true);
        }

        $filename =  time() . '.' . $file->extension();

        $file->move($folderPath, $filename);

        return "$folder/$filename";

        /* $splitRegex = (env('IS_LOCAL') == true)
        ? 'public\\'
        : '/public/'; */

        /* $path = explode($splitRegex, $filename)[1];
        // $path = str_replace('\\', '/', explode('public/', $filename)[1]);

        return $path; */
    }

    public function generateRandomValues($length = 8, $numOnly = false)
    {
        $nums = '1234567890';
        $values = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($numOnly) {
            return $this->makeString($nums, $length);
        }

        return $this->makeString("{$values}{$nums}", $length);
    }

    public function deleteFile(string $path)
    {
        File::delete(public_path($path));

        return true;
    }

    public function generateUUID()
    {
        $uuid = Uuid::uuid4();
        $uuidString = $uuid->toString();

        return $uuidString;
    }

    public function amountInDecimal($amount)
    {
        return round((float) $amount, 2);
    }

    public function convertDateTimeFormat($date)
    {
        $dateTime = new DateTime($date, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('UTC'));
        $utcFormat = $dateTime->format('Y-m-d\TH:i:s.u\Z');

        return $utcFormat;
    }

    public function getHumanReadableDate($dateTime)
    {
        $date = Carbon::parse($dateTime);
        $now = Carbon::now();

        if ($date->diffInSeconds($now) < 60) {
            return $date->diffInSeconds($now) . ' seconds ago';
        } elseif ($date->diffInMinutes($now) < 60) {
            return $date->diffInMinutes($now) . ' minutes ago';
        } elseif ($date->isYesterday()) {
            return 'yesterday';
        } else {
            return $date->format('Y-m-d');
        }
    }
    public function parsePaginationData(?array $data)
    {
        if ($data === null) {
            return null;
        }


        $data['nextPageUrl'] = $data['next_page_url'];
        $data['totalPage'] = $data['last_page'];

        unset(
            $data['current_page'],
            $data['first_page_url'],
            $data['from'],
            $data['last_page'],
            $data['last_page_url'],
            $data['links'],
            $data['path'],
            $data['per_page'],
            $data['prev_page_url'],
            $data['to'],
            $data['next_page_url']
        );

        return $data;
    }
    public function formatCurrency($amount)
    {
        // return number_format($amount, 2, '.', ',');
        $formattedAmount = (float) str_replace(',', '', number_format($amount, 2, '.', ''));
        return $formattedAmount;
    }
}
