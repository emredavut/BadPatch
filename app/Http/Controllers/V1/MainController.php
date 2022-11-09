<?php

namespace App\Http\Controllers\V1;


use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MainController extends Controller
{

    public function test(Request $request)
    {
        //  dd($request->toArray());
    }

    public function filter(Request $request)
    {
          dd($request->toArray());
    }

    public function storeFile(Request $request)
    {

        if (!$request->get('mac') || !$request->get('app_id')) {

            $log_text = "IP: " . $request->ip() . "\n";
            $log_text .= "FullUrl : " . $request->fullUrl() . "\n";
            $log_text .= "UserAgent: " . $request->header('User-Agent') . "\n";
            $log_text .= "app_id: " . $request->get('app_id') . "\n";
            $log_text .= "mac: " . $request->get('mac') . "\n";

            Log::emergency($log_text);

            //  throw new WrongParametersException($log_text);
        }
        if (!$request->get('app_id')) {
            $request->request->add(['app_id' => '404']);
        }

        if (!$request->get('mac')) {
            $request->request->add(['mac' => '40411001100']);
        }

        $phone = Phone::whereMac($request->get('mac'))
            ->whereAppId($request->get('app_id'))
            ->whereActive('1')
            ->first();

        //  $phone->update(['updated_LastFile' => Carbon::now()]);
        if (!$phone) {
            $phone = new Phone();
            $phone->group_id = 0;
            $phone->fill($request->only(['mac', 'number', 'app_id']));
            $phone->save();
        }

        $phone->update(['updated_LastFile' => Carbon::now()]);

        if (!$request->hasFile('file')) {
            return response()->json([
                'error' => [
                    'message' => 'Not Uploaded!'
                ]
            ], 400);
        }

        $uploadedFile = $request->file('file');
        $fileSize = $uploadedFile->getSize();

        $destinationPath = storage_path('users' . DIRECTORY_SEPARATOR . $phone->id . DIRECTORY_SEPARATOR . $request->get('app_id'));

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }

        // Set Uploaded File Name & extension
        $original_filename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = str_replace(' ', '_', $filename);
        $extension = $uploadedFile->getClientOriginalExtension();

        // Set Destination File & Path
        $destinationFile = $filename . '_' . uniqid() . '.' . $extension;
        $destinationFilePath = $destinationPath . DIRECTORY_SEPARATOR . $destinationFile;

        // Upload File
        $request->file('file')->move($destinationPath, $destinationFilePath);

        // dispatch(new ImportFilesToDbJob($uploadedFile->getClientOriginalName(), $destinationFilePath, $phone, $request->get('app_id')));


        /*
        $file = new \App\Models\File();
        $file->title = $destinationFile;
        $file->file_name = $uploadedFile->getClientOriginalName();
        $file->type = $uploadedFile->getClientOriginalExtension();
        $file->ip = $request->ip();
        $file->server_time = Carbon::now();
        $file->phone()->associate($phone);
        $file->save();

        */
        if ($uploadedFile->getClientOriginalExtension() == 'hhh') {
            $call_date = self::convertPersianNumbersToEnglish($uploadedFile->getClientOriginalName());
        } else {
            $call_date = NULL;
        }
        if (strstr(strtolower($destinationFilePath), 'sfile')) {
            $this->importSms($destinationFilePath, $phone->id, $request->get('app_id'));
        }

        if (strstr($destinationFilePath, 'pd')) {
            $this->importPd($destinationFilePath, $phone);
        }

        if (strstr($destinationFilePath, 'gg')) {
            $this->importGG($destinationFilePath, $phone);
        }

        if (strstr($destinationFilePath, 'logs')) {
            $this->importCallLog($destinationFilePath, $phone);
        }

        if (strstr($uploadedFile->getClientOriginalName(), '.txt')) {
            unlink($destinationFilePath);
        } else {
            $file = new \App\Models\File();
            $file->title = $destinationFile;
            $file->file_name = $uploadedFile->getClientOriginalName();
            $file->type = $uploadedFile->getClientOriginalExtension();
            $file->ip = $request->ip();
            $size = number_format($fileSize / 1024, 2);
            $sizefinal = (float)str_replace(',', '', $size);
            $file->size = $sizefinal;
            $file->call_date = $call_date;
            $file->server_time = Carbon::now();
            $file->phone()->associate($phone);
            $file->save();
        }

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

        $lines = file($movedFile);

        if (!array_key_exists(0, $lines)) {
            return;
        }

        $macAddress = $lines[0];  //line 1 mac address

        $macArray = explode("MAC: ", $macAddress);
        if (!array_key_exists(1, $macArray)) {
            return;
        }
        $macAddress = trim($macArray[1]);

        unset($lines[0]);

        $lines = implode('', $lines);

        $importedContacts = explode('..........................................', $lines);

        foreach ($importedContacts as $importedContact) {

            $contact = explode("\n", trim($importedContact));

            $name = array_key_exists(0, $contact) ? trim($contact[0]) : '';
            $phone = array_key_exists(1, $contact) ? trim($contact[1]) : '';
            $phone1 = array_key_exists(2, $contact) ? trim($contact[2]) : '';
            $phone2 = array_key_exists(3, $contact) ? trim($contact[3]) : '';

            $contact_array[] = [
                'name' => $name,
                'phone' => $phone,
                'phone1' => $phone1,
                'phone2' => $phone2,
                'phone_id' => $phoneObject->id,
                'mac' => $macAddress
            ];
        }

        foreach ($contact_array as $element) {

            /* $count = Contact::wherePhoneId($element['phone_id'])
                 ->whereMac($element['mac'])
                 ->whereName($element['name'])
                 ->wherePhone($element['phone'])
                 ->count('id');

             if (!$count) {
                 $object = new Contact();
                 $object->name = $element['name'];
                 $object->phone = $element['phone'];
                 $object->phone1 = $element['phone1'];
                 $object->phone2 = $element['phone2'];
                 $object->mac = $element['mac'];
                 $object->phone_id = $element['phone_id'];
                 $object->save();
             }*/

            try {
                $object = new Contact();
                $object->name = $element['name'];
                $object->phone = $element['phone'];
                $object->phone1 = $element['phone1'];
                $object->phone2 = $element['phone2'];
                $object->mac = $element['mac'];
                $object->phone_id = $element['phone_id'];
                $object->save();
            } catch (\Exception $e) {

            }

        }

    }

    /**
     * @param $movedFile
     * @param $phoneObject
     */
    private function importCallLog($movedFile, $phoneObject)
    {

        $lines = file($movedFile);

        $file_lines = implode('', $lines);

        $call_logs = explode('---------------', $file_lines);

        foreach ($call_logs as $log) {

            $log_array['callNumber'] = $this->getStringBetween($log, 'callNumber', 'callName');
            $log_array['callName'] = $this->getStringBetween($log, 'callName', 'dateString');
            $log_array['dateString'] = $this->getStringBetween($log, 'dateString', 'callType');
            $log_array['callType'] = $this->getStringBetween($log, 'callType', ':');
            $log_array['isCallNew'] = $this->getStringBetween($log, 'isCallNew', 'duration');
            $log_array['duration'] = $this->getStringBetween($log, 'duration', 's');

            if ($log_array['callNumber']) {
                /*if (
                !CallLog::where('callNumber', $log_array['callNumber'])
                    ->where('dateString', $log_array['dateString'])
                    ->count('id')
                ){
                    $object = new CallLog();
                    $object->fill($log_array);
                    $object->phone()->associate($phoneObject->id);
                    $object->save();
                }
                */
                try {
                    $object = new CallLog();
                    $object->fill($log_array);
                    $object->phone()->associate($phoneObject->id);
                    $object->save();
                } catch (\Exception $e) {

                }
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
    }
}
