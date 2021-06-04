<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\CareerSupportModelsWebinarBiasa;
// use App\Models\CareerSupportModelsNotificationWebinarnormalModel;
use App\Models\StudentModel;
use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WebinarNormalController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbParticipant;
    private $tbNotif;
    private $tbStudent;
    private $tbOrder;
    private $tbSchool;

    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbSchool = SchoolModel::tableName();
    }

    public function listNormalWebinar()
    {
        //count data fom tabel participant to get the registerd
        try {
            $count = "select count('part.webinar_id') from " . $this->tbParticipant . " as part where part.webinar_id = web.id";
            $datas = DB::select("select web.id as webinar_id, web.event_name, web.event_picture, web.event_date, web.start_time, web.end_time, web.price, (500) as quota, (" . $count . ") as registered from " . $this->tbWebinar . " as web where web.event_date > current_date");

            return $this->makeJSONResponse($datas, 200);
        } catch (Exception $e) {
            echo $e;
        }
    }

    //get the detail of webinar
    public function detailNormalWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $webinar = DB::table($this->tbWebinar)
                    ->where('id', '=', $webinar_id)
                    ->get();

                $registered = DB::table($this->tbWebinar, 'webinar')
                    ->leftJoin($this->tbOrder . ' as pesan', 'webinar.id', '=', 'pesan.webinar_id')
                    ->where('webinar.id', '=', $webinar_id)
                    ->where('pesan.status', '!=', 'order')
                    ->where('pesan.status', '!=', 'expire')
                    ->select('pesan.id')
                    ->get();

                if (count($webinar) > 0) {
                    $response = array(
                        "event_id"      => $webinar_id,
                        "event_name"    => $webinar[0]->event_name,
                        "event_date"    => $webinar[0]->event_date,
                        "start_time"    => $webinar[0]->start_time,
                        "end_time"      => $webinar[0]->end_time,
                        "event_picture" => $webinar[0]->event_picture,
                        "registered"    => count($registered),
                        'quota'         => 500
                    );

                    return $this->makeJSONResponse($response, 200);
                } else {
                    return $this->makeJSONResponse(['message' => 'Data not found'], 202);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    //get the detail of webinar with the participant list
    public function detailNormalWebinarWithStudent($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $webinar = DB::table($this->tbWebinar)
                    ->where('id', '=', $webinar_id)
                    ->get();

                $registered = DB::table($this->tbWebinar, 'webinar')
                    ->leftJoin($this->tbParticipant . ' as participant', 'webinar.id', '=', 'participant.webinar_id')
                    ->leftJoin($this->tbOrder . ' as pesan', 'participant.id', '=', 'pesan.participant_id')
                    ->where('webinar.id', '=', $webinar_id)
                    ->where('pesan.status', '!=', 'order')
                    ->where('pesan.status', '!=', 'expire')
                    ->select('participant.student_id', 'pesan.status')
                    ->get();

                if (count($webinar) > 0) {
                    $student = array();

                    if (count($registered) > 0) {
                        for ($i = 0; $i < count($registered); $i++) {
                            $temp = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                ->leftJoin($this->tbSchool . ' as school', 'student.school_id', '=', 'school.id')
                                ->where('student.id', '=', $registered[$i]->student_id)
                                ->select('student.name as student_name', 'school.name as school_name')
                                ->get();

                            $student[$i] = array(
                                "student_id"  => $registered[$i]->student_id,
                                "student_name" => $temp[0]->student_name,
                                "school_name" => $temp[0]->school_name,
                                "participant_status" => $registered[$i]->status
                            );
                        }
                    }

                    $response = array(
                        "event_id"      => $webinar_id,
                        "event_name"    => $webinar[0]->event_name,
                        "event_date"    => $webinar[0]->event_date,
                        "start_time"    => $webinar[0]->start_time,
                        "end_time"      => $webinar[0]->end_time,
                        "event_picture" => $webinar[0]->event_picture,
                        "registered"    => count($registered),
                        'quota'         => 500,
                        'student'       => $student
                    );

                    return $this->makeJSONResponse($response, 200);
                } else {
                    return $this->makeJSONResponse(['message' => 'Data not found'], 202);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    public function addNormalWebinar(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'event_name' => 'required',
            'event_date' => 'required',
            'event_picture' => 'required|mimes:jpg,jpeg,png|max:2000',
            'event_link' => 'required|url',
            'start_time' => 'required',
            'end_time' => 'required',
            'price' => 'numeric|required',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 202);
        } else {
            $duplicatename = DB::table($this->tbWebinar)
                ->where("event_name", "=", $request->event_name)
                ->get();
            $samedaytime = DB::table($this->tbWebinar)
                ->where("event_date", "=", $request->event_date)
                ->where("start_time", "=", $request->start_time)
                ->where("end_time", "=", $request->end_time)
                ->get();
            if (count($duplicatename) > 0) {
                $message = "webinar data already exist !";
                $code = 200;
                return $this->makeJSONResponse(["message" => $message], $code);
            } else if (count($samedaytime) > 0) {
                $message = "same webinar date and time already exist,please select another day or time!";
                $code = 200;
                return $this->makeJSONResponse(["message" => $message], $code);
            } else {
                if ($request->event_date > date('Y-m-d')) {
                    if ($file = $request->file('event_picture')) {
                        try {
                            $path = $file->store('webinarNormal', 'uploads');
                            $webinar = array(
                                'event_name' => $request->event_name,
                                'event_link' => $request->event_link,
                                'event_date' => $request->event_date,
                                'event_picture' => $path,
                                'start_time' => $request->start_time,
                                'end_time' => $request->end_time,
                                'price' => $request->price,
                                'is_deleted'=>true
                            );
                            DB::table($this->tbWebinar)->insert($webinar);
                        } catch (Exception $e) {
                            echo $e;
                        }
                        return $this->makeJSONResponse(['message' => 'successfully save data webinar to database'], 200);
                    }
                } else {
                    return $this->makeJSONResponse(['message' => 'the event must be after today!'], 202);
                }
            }
        }
    }
    public function editWebinar(Request $request, $webinar_id)
    {
        $validation = Validator::make($request->all(), [
            'event_name' => 'required',
            'event_date' => 'required',
            'event_link' => 'required|url',
            'start_time' => 'required',
            'end_time' => 'required',
            'price' => 'numeric|required',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 202);
        } else {
            $find = DB::table($this->tbWebinar)
                ->where('id', '=', $webinar_id)
                ->select('id as webinar_id')
                ->get();

            $datetime = Carbon::now();
            $datetime->toDateTimeString();
            //cek duplicate
            if ($find) {
                $update = array(
                    'event_name' => $request->event_name,
                    'event_date' => $request->event_date,
                    'event_link' => $request->event_link,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'price' => $request->price,
                    'modified' => $datetime
                );
                DB::table($this->tbWebinar)
                    ->where('id', '=', $webinar_id)
                    ->update($update);

                $message = "sucessfully update data webinar!";
                $code = 200;
                return response()->json(["message" => $message], $code);
            }
        }
    }
}
