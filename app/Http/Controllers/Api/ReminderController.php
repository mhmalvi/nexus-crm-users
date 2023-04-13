<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FollowUp;
use Exception;
use PhpParser\Node\Stmt\TryCatch;

class ReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $follow_up = FollowUp::all();
            if ($follow_up) {
                return response()->json([
                    'status' => 200,
                    'message' => "success",
                    'data' => $follow_up
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $this->attributes['r_time'] = date('H:i', strtotime($value));
        try {
            $follow_up = FollowUp::create([
                'event_title' => $request->event_title,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'description' => $request->description,
                'priority' => $request->priority,
            ]);
            // dd($follow_up);
            if ($follow_up) {
                return response()->json([
                    'status' => 201,
                    'message' => "success",
                    'data' => $follow_up
                ], 201);
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => "failed",
                    'data' => $follow_up
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // dd($id);
            $follow_up = FollowUp::find($id);
            if ($follow_up) {
                $follow_up->event_title = $request->event_title;
                $follow_up->start_time = $request->start_time;
                $follow_up->end_time = $request->end_time;
                $follow_up->description = $request->description;
                $follow_up->priority = $request->priority;
                $save = $follow_up->save();
                if ($save) {
                    return response()->json([
                        'status' => 201,
                        'message' => "success",
                        'data' => $follow_up
                    ], 201);
                }
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "not found"
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
