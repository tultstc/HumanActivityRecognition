<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('models')->insert([
            'id' => 1,
            'name' => 'Model Default',
            'url' => 'model/yolo11n.pt',
            'status' => 1,
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}