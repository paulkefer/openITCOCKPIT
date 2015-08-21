<?php

$data = json_decode('[
    {
        "type"    : "view",
        "name"    : "db_view_122",
        "depends" : [
            "db_table_15",
            "db_table_14",
            "db_table_1"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_2",
        "depends" : [
            "db_table_4",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_3",
        "depends" : [
            "db_table_5",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_4",
        "depends" : [
            "db_table_10",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_5",
        "depends" : [
            "db_table_10",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_6",
        "depends" : [
            "db_table_10"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_7",
        "depends" : [
            "db_view_6",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_8",
        "depends" : [
            "db_table_15",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_9",
        "depends" : [
            "db_table_18",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_10",
        "depends" : [
            "db_table_18",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_11",
        "depends" : [
            "db_table_24",
            "db_table_14"
        ]
    }, {
        "type"    : "view",
        "name"    : "db_view_12",
        "depends" : [
            "db_table_28",
            "db_table_14"
        ]
    }, {
        "type"    : "table",
        "group"   : "Mapping",
        "name"    : "db_table_1",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_2",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_3",
        "depends" : [
            "SASProject.egp"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_4",
        "depends" : [
            "SASProject.egp",
            "db_table_3"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_5",
        "depends" : [
            "db_table_7",
            "ETL process 1"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_6",
        "depends" : [
            "ETL process 1"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_7",
        "depends" : [
            "ETL process 1"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_8",
        "depends" : [
            "query5.sql"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_9",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_10",
        "depends" : [
            "db_table_12",
            "ETL process 2"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_11",
        "depends" : [
            "ETL process 2"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_12",
        "depends" : [
            "ETL process 2"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_13",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_14",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_15",
        "depends" : [
            "db_table_17",
            "ETL process 3"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_16",
        "depends" : [
            "ETL process 3"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_17",
        "depends" : [
            "ETL process 3"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_18",
        "depends" : [
            "db_table_20",
            "ETL process 4"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_19",
        "depends" : [
            "ETL process 4"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_20",
        "depends" : [
            "ETL process 4"
        ]
    }, {
        "type"    : "table",
        "group"   : "Mapping",
        "name"    : "db_table_21",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_22",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_23",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_24",
        "depends" : [
            "query6.sql"
        ]
    }, {
        "type"    : "table",
        "group"   : "Mapping",
        "name"    : "db_table_25",
        "depends" : []
    }, {
        "type"    : "table",
        "group"   : "Mapping",
        "name"    : "db_table_26",
        "depends" : []
    }, {
        "type"    : "table",
        "group"   : "Mapping",
        "name"    : "db_table_27",
        "depends" : []
    }, {
        "type"    : "table",
        "name"    : "db_table_28",
        "depends" : [
            "db_table_30",
            "ETL process 5"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_29",
        "depends" : [
            "ETL process 5"
        ]
    }, {
        "type"    : "table",
        "name"    : "db_table_30",
        "depends" : [
            "ETL process 5"
        ]
    }, {
        "type"    : "query",
        "group"   : "Management",
        "name"    : "query1.sql",
        "depends" : [
            "db_table_9",
            "db_table_23",
            "db_table_22"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data1",
        "name"    : "query2.sql",
        "depends" : [
            "db_table_4"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data1",
        "name"    : "query3.sql",
        "depends" : [
            "db_table_4"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data1",
        "name"    : "query4.sql",
        "depends" : [
            "db_table_4"
        ]
    }, {
        "type"    : "query",
        "group"   : "Miscellaneous",
        "name"    : "query5.sql",
        "depends" : [
            "db_view_9",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Miscellaneous",
        "name"    : "query6.sql",
        "depends" : [
            "db_view_12",
            "db_table_25",
            "db_table_26",
            "db_table_27",
            "db_view_9",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query7.sql",
        "depends" : [
            "db_view_4",
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query8.sql",
        "depends" : [
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query9.sql",
        "depends" : [
            "db_view_9",
            "db_view_4",
            "db_view_2",
            "db_view_7",
            "db_table_18"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query10.sql",
        "depends" : [
            "db_table_2",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query11.sql",
        "depends" : [
            "db_view_2"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query12.sql",
        "depends" : [
            "db_view_3"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query13.sql",
        "depends" : [
            "db_view_4"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query14.sql",
        "depends" : [
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query15.sql",
        "depends" : [
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query16.sql",
        "depends" : [
            "db_view_11",
            "db_table_27"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query17.sql",
        "depends" : [
            "db_view_7"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query18.sql",
        "depends" : [
            "db_table_18",
            "db_table_13",
            "db_table_14",
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query19.sql",
        "depends" : [
            "db_table_18",
            "db_table_13",
            "db_table_14",
            "db_table_8"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query20.sql",
        "depends" : [
            "db_view_9",
            "db_view_10",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query21.sql",
        "depends" : [
            "db_view_9",
            "db_view_10",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data2",
        "name"    : "query22.sql",
        "depends" : [
            "db_view_4",
            "db_view_5",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data3",
        "name"    : "query23.sql",
        "depends" : [
            "db_table_15",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Data4",
        "name"    : "query24.sql",
        "depends" : [
            "db_table_28",
            "db_table_14"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query25.sql",
        "depends" : [
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query26.sql",
        "depends" : [
            "db_view_9"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query27.sql",
        "depends" : [
            "db_view_8"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query28.sql",
        "depends" : [
            "db_view_3"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query29.sql",
        "depends" : [
            "db_view_11"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query30.sql",
        "depends" : [
            "db_view_12"
        ]
    }, {
        "type"    : "query",
        "group"   : "Validation",
        "name"    : "query31.sql",
        "depends" : [
            "db_view_9",
            "db_view_8",
            "db_table_21",
            "db_table_14",
            "db_table_13"
        ]
    }, {
        "type"    : "sas",
        "name"    : "SASProject.egp",
        "depends" : []
    }, {
        "type"    : "extract",
        "name"    : "ETL process 1",
        "depends" : []
    }, {
        "type"    : "extract",
        "name"    : "ETL process 2",
        "depends" : []
    }, {
        "type"    : "extract",
        "name"    : "ETL process 3",
        "depends" : []
    }, {
        "type"    : "extract",
        "name"    : "ETL process 4",
        "depends" : []
    }, {
        "type"    : "extract",
        "name"    : "ETL process 5",
        "depends" : []
    }, {
        "type"    : "database",
        "name"    : "Data store 1",
        "depends" : [
            "query2.sql",
            "query3.sql",
            "query4.sql"
        ]
    }, {
        "type"    : "database",
        "name"    : "Data store 2",
        "depends" : [
            "query7.sql",
            "query8.sql",
            "query9.sql",
            "query10.sql",
            "query11.sql",
            "query12.sql",
            "query13.sql",
            "query14.sql",
            "query15.sql",
            "query16.sql",
            "query17.sql",
            "query18.sql",
            "query19.sql",
            "query20.sql",
            "query21.sql",
            "query22.sql"
        ]
    }, {
        "type"    : "database",
        "name"    : "Data store 3",
        "depends" : [
            "query23.sql"
        ]
    }, {
        "type"    : "database",
        "name"    : "Data store 4",
        "depends" : [
            "query24.sql"
        ]
    }, {
        "type"    : "report",
        "group"   : "Intermediate",
        "name"    : "Intermediate step 1",
        "depends" : [
            "Data store 1"
        ]
    }, {
        "type"    : "report",
        "group"   : "Intermediate",
        "name"    : "Intermediate step 2",
        "depends" : [
            "Data store 1"
        ]
    }, {
        "type"    : "report",
        "name"    : "Report 1",
        "depends" : [
            "Data store 2"
        ]
    }, {
        "type"    : "report",
        "name"    : "Report 2",
        "depends" : [
            "Data store 2"
        ]
    }, {
        "type"    : "report",
        "name"    : "Report 3",
        "depends" : [
            "Data store 2"
        ]
    }, {
        "type"    : "report",
        "name"    : "Report 4",
        "depends" : [
            "Data store 2"
        ]
    }, {
        "type"    : "report",
        "name"    : "Report 5",
        "depends" : [
            "Data store 3",
            "Intermediate step 2"
        ]
    }, {
        "type"    : "report",
        "group"   : "Intermediate",
        "name"    : "Intermediate step 3",
        "depends" : [
            "Report 5",
            "Data store 1"
        ]
    }, {
        "type"    : "report",
        "name"    : "Report 6",
        "depends" : [
            "Data store 4",
            "Intermediate step 1"
        ]
    }, {
        "type"    : "report",
        "group"   : "Reporting",
        "name"    : "Upstream report 1",
        "depends" : [
            "Data store 2"
        ]
    }, {
        "type"    : "report",
        "group"   : "Reporting",
        "name"    : "Upstream report 2",
        "depends" : [
            "Data store 2"
        ]
    }, {
        "type"    : "report",
        "group"   : "Reporting",
        "name"    : "Upstream report 3",
        "depends" : [
            "Data store 2"
        ]
    }
]
', true);


function getRandomNames($data){
# var_dump($data);
# die();
	$key = array_rand($data, 1);
return array($data[$key]['name']);
	$result = array();
	foreach($keys as $key){
		$result[] = $data[$key]['name'];
	}
	return $result;
}



for($i = 0; $i < 1000; $i++){
	$item = array(
		'type' => 'foobar',
		'name' => 'superschaaf ' . $i,
		//		'depends' => [],
		// 'depends' => getRandomNames($data),
	);
	$random = rand(0,1);
	if(0){
		$item['depends'] = [];
	}else{
		$item['depends'] = getRandomNames($data);
	}

	$data[] = $item;
}

echo json_encode($data);
