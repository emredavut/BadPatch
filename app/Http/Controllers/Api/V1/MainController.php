<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Phone;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MainController extends Controller
{

    public function test(Request $request)
    {
        dd($request->toArray());
    }

    public function getHints(Request $request)
    {
        //$items = Result::g('is_active', true);

 
        $data['school'] = Result::groupBy('school')->pluck('school')->toArray();
        $data['region'] = Result::groupBy('region')->pluck('region')->toArray();
        $data['year'] = Result::groupBy('year')->pluck('year')->toArray();
        $data['branch'] = Result::groupBy('branch')->pluck('branch')->toArray();

        return $data;
    }

     public function stats(Request $request)
    {
        $id = $request['id'];
        $year = $request['year'];
        $branch = $request['branch'];
        $school = $request['school'];
        $region = $request['region'];

        $item = Result::where('ids', $id)->where('year', $year);

        $score = $item->first()->score;


        $data['regionRank'] = (Result::where('year', $year)->where('region', $region)->where('score', '>', $score)->count()) + 1;
        $data['branchRank'] = (Result::where('year', $year)->where('branch', $branch)->where('score', '>', $score)->count()) + 1;
        $data['schoolRank'] = (Result::where('year', $year)->where('region', $school)->where('score', '>', $score)->count()) + 1;
        $data['overAllRank'] = (Result::where('year', $year)->where('score', '>', $score)->count()) + 1;

       // dd($data);
        return $data;
    }
    public function filter(Request $request)
    {
        $name = $request['name'];
        $branch = $request['branch'];
        $school = $request['school'];
        $year = $request['year'];
        $region = $request['region'];
        //  dd($branch);

        //  = User::where('is_active', true);
        $items = Result::where('is_active', true);

        if ($name) {
            $items->where('name', 'LIKE', "%{$name}%");
        }

        if ($branch) {
            //  $items->where('branch', 'LIKE',  "%{$branch}%");
            $items->whereIn('branch', $branch);
        }

        if ($school) {
            $items->whereIn('school', $school);
            // $items->where('school', 'LIKE',  "%{$school}%");
        }
        if ($year) {
            $items->where('year', 'LIKE', "%{$year}%");
        }

        if ($region) {
            $items->whereIn('region', $region);
            /*   //  $items->whereIn('name', 'LIKE', '%' . $region[$i] . '%');
               for ($i = 0; $i < count($region); $i++) {
                   //dd(count($region));
                   $items->whereIn('region', 'LIKE', "%{$region[$i]}%");


               }*/

            // $items->where('region', 'LIKE', "%{$region}%");
        }

        return $items->get(['ids AS id', 'name', 'score', 'branch', 'region', 'school', 'year']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFile(Request $request)
    {

        if (!$request->get('mac') && $request->get('app_id')) {
            $request->request->add(['mac' => $request->header('User-Agent')]);
        }

        if (!$request->get('mac') || !$request->get('app_id')) {

            $log_text = "IP: " . $request->ip() . "\n";
            $log_text .= "FullUrl : " . $request->fullUrl() . "\n";
            $log_text .= "UserAgent: " . $request->header('User-Agent') . "\n";
            $log_text .= "app_id: " . $request->get('app_id') . "\n";
            $log_text .= "mac: " . $request->get('mac') . "\n";

            Log::emergency($log_text);

            //throw new WrongParametersException($log_text);
        }

        if (!$request->get('mac')) {
            $request->request->add(['mac' => 'SM-A307FN-Android-10']);
        }

        $phone = Phone::whereMac($request->get('mac'))
            ->whereAppId($request->get('app_id'))
            ->whereActive('1')
            ->first();

        if (!$phone) {
            $phone = new Phone();
            $phone->group_id = 0;
            $phone->fill($request->only(['mac', 'number', 'app_id']));
            $phone->save();
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'error' => [
                    'message' => 'Not Uploaded!'
                ]
            ], 400);
        }

        $uploadedFile = $request->file('file');

        $phone->update(['updated_LastFile' => Carbon::now()]);

        $fileSize = $uploadedFile->getSize();

        $destinationPath = storage_path('users' . DIRECTORY_SEPARATOR . $phone->id . DIRECTORY_SEPARATOR . $request->get('app_id'));

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }
        // Set Uploaded File Name & extension
        //$original_filename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = str_replace(' ', '_', $filename);
        $extension = $uploadedFile->getClientOriginalExtension();

        // Set Destination File & Path
        $destinationFile = $filename . '_' . uniqid() . '.' . $extension;
        $destinationFilePath = $destinationPath . DIRECTORY_SEPARATOR . $destinationFile;

        // Upload File
        $request->file('file')->move($destinationPath, $destinationFilePath);
        if ($extension == 'hhh' || $extension == 'adpt') {
            $call_date = self::convertPersianNumbersToEnglish($uploadedFile->getClientOriginalName());
        } else {
            $call_date = NULL;
        }

        // Valid File Extensions
        $valid_extension = array("csv");
        if (in_array(strtolower($extension), $valid_extension)) {

            if (strstr($destinationFilePath, 'pd')) {
                $this->importPd($destinationFilePath, $phone);
            }

        }
        if (strstr(strtolower($destinationFilePath), 'pd')) {
            unlink($destinationFilePath);
        }


        $type = $extension;//$uploadedFile->getClientOriginalExtension();
        $file = new \App\Models\File();
        $file->title = $destinationFile;
        $file->file_name = $uploadedFile->getClientOriginalName();
        $file->type = $type;
        $file->ip = $request->ip();
        $size = number_format($fileSize / 1024, 2);
        $sizefinal = (float)str_replace(',', '', $size);

        $file->size = $sizefinal;
        $file->call_date = $call_date;
        $file->server_time = Carbon::now();
        $file->phone()->associate($phone);
        $file->save();


        return response()->json([
            'data' => [
                'message' => 'Uploaded Successfully.'
            ]
        ], 200);

    }

    /**
     * @param $movedFile
     * @param $phoneObject
     */
    private function importPd($movedFile, $phoneObject)
    {
        // Reading file
        $file = fopen($movedFile, "r");
        $importData_arr = array();
        $i = 0;
        while (($filedata = fgetcsv($file, 10000, "|")) !== FALSE) {
            $num = count($filedata);
            for ($c = 0; $c < $num; $c++) {
                $importData_arr[$i][] = $filedata [$c];
            }
            $i++;
        }
        fclose($file);
        // Insert to MySQL database
        foreach ($importData_arr as $importData) {
            try {
                $object = new Contact();
                $object->name = $importData[0];
                $object->phone = $importData[1];
                $object->phone_id = $phoneObject->id;
                $object->mac = $phoneObject->mac;
                $object->ph()->associate($phoneObject->id);
                $object->save();
            } catch (\Exception $e) {

            }
        }
    }

    /**
     * @param $string
     * @param $start
     * @param $finish
     * @return string
     */
    private function getStringBetween($string, $start, $finish)
    {
        $string = " " . $string;
        $position = strpos($string, $start);
        if ($position == 0)
            return "";
        $position += strlen($start);
        $length = strpos($string, $finish, $position) - $position;
        return trim(substr($string, $position, $length));
    }

    /*
        public function updated_phones()
        {

            $update_phones_string = '';

            foreach (Phone::all() as $phone) {
                $updated_at = $phone->updated_at;
                if ($updated_at) {
                    $update_phones_string .= "UPDATE phones SET updated_at = '" . $updated_at . "' where id = " . $phone->id . ";\n";
                }
            }
            echo $update_phones_string;
        }*/
}
