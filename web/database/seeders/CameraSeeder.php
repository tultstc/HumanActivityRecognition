<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CameraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('cameras')->insert([
            'id' => 1,
            'name' => 'Camera 1',
            'stream_url' => 'rtsp://admin:Admin123456*@@192.168.8.191:554/Streaming/channels/101',
            'config' => '{
                            "conf":0.5,
                            "label_conf":[0,1,2,3,4],
                            "annotators":{
                                "box_annotator":{
                                    "type":"BoxAnnotator",
                                    "thickness":2
                                },
                                "label_annotator":{
                                    "type":"LabelAnnotator",
                                    "text_position":"TOP_CENTER",
                                    "text_thickness":2,
                                    "text_scale":1
                                }
                            }
                        }',
            'model_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}