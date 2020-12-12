<?php

namespace App\Imports;

use App\Classification;
use App\Disease;
use App\Gene;
use App\Inheritance;
use App\Submission;
use App\Submitter;
use Carbon\Carbon;
use DateTime;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class SubmissionsImport implements OnEachRow, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function  __construct($submitted_run_date)
    {
        $this->submitted_run_date = $submitted_run_date;
            //dd("ddsadsad");
            //dd($submitted_run_date);
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        //dd($this->submitted_run_date);

        //dd($rowIndex);
        if($rowIndex > 12) {
            //dd($row);
            if(isset($row["submission_id"]) && isset($row["hgnc_id"]) && isset($row["disease_id"]) && isset($row["moi_id"]) && isset($row["date"])){
                //dd($rowIndex);

                $row["disease_id"] = preg_replace('/\s+/', '', $row["disease_id"]);
                $row["hgnc_id"] = preg_replace('/\s+/', '', $row["hgnc_id"]);

                $classification_missing = Classification::curie("GENCC:000000")->first();
                $classification_record = Classification::curie($row["classification_id"])->first();
                $inheritance_missing = Inheritance::curie("HP:0000005")->first();
                $inheritance_record = Inheritance::curie($row["moi_id"])->first();
                $disease_record = Disease::curie($row["disease_id"])->first();
                $gene_record = Gene::curie($row['hgnc_id'])->first();
                //dd($gene_record);
                $submitter_record = Submitter::curie($row["submitter_id"])->first();

                if (!isset($gene_record)) {
                    echo "GENE ERROR - NOT IN HGNC ERROR -- '" . $row["hgnc_id"] . "\n";
                }

                if(isset($gene_record->title)){
                    if ($gene_record->title != $row['hgnc_symbol']) {
                        echo "GENE ERROR - GENE SYMBOLS DON'T MATCH -- HGNC: '" . $gene_record->title . "' v.s. Submitted: '" . $row["hgnc_symbol"] . "\n";
                        unset($gene_record);
                    }
                }

                if (is_numeric($row['date'])) {
                    $date_record = true;
                } elseif (DateTime::createFromFormat('Y-m-d', $row['date']) !== FALSE) {
                    $date_record = true;
                } else {
                    echo "DATE ERROR - DATE NOT SET CORRECTLY -- '" . $row["date"] . "' - '" . $row["hgnc_id"] . "\n";
                    $row['date'] = "";
                    unset($date_record);
                }

                // If disease isn't found... see if MONDO has
                if(!isset($disease_record) || !isset($disease_record->title)){

                   if(($row["disease_id"] == "null") || ($row["disease_id"] == "NULL")){
                        echo "DISEASE ERROR -- '" . $row["disease_id"] . "\n";
                    } else {
                    //dd($row["disease_id"]);
                    //$query = trim(preg_replace('/\s+/', '', $row["disease_id"]));
                    $query = $row["disease_id"];
                    $query = preg_replace("/[^a-zA-Z0-9:]/", "", $query);
                    //dd($query);
                    $client = new Client(['base_uri' => 'https://api.monarchinitiative.org/api/', 'http_errors' => false]);
                    //dd($client);
                    $response = $client->request('GET', 'bioentity/disease/'. $query);
                    //dd($response->getStatusCode());
                    if($response->getStatusCode() == 200) {
                        //dd("sdfsdfds");
                        $body = $response->getBody();
                        //dd($body)
                        $data = json_decode($response->getBody());
                        //dd($data->label);
                        //$data =
                        // Only save some of the diseases
                        //if (preg_match('(OMIM:|MONDO:|DOID:|Orphanet:|HP:)', $val) === 1) {
                        if (preg_match('(MONDO:)', $data->id) === 1) {

                            //dd("test");
                            $type = explode(":", $data->id);
                            $type = $type[0];
                            $title = $data->label;
                            $uuid = str_replace(':', '_', $data->id);

                            $mondo = Disease::updateOrCreate(
                                [
                                    'curie' => $data->id,
                                    'type' => $type,
                                    'title' => $title,
                                    'uuid'  => $uuid
                                ],
                                [
                                    'curie' => $data->id,
                                    'type' => $type,
                                    'title' => $title,
                                    'uuid'  => $uuid
                                ]
                            );


                            //dd("test");
                            $type = explode(":", $query);
                            $type = $type[0];
                            $title = $query;
                            $uuid = str_replace(':', '_', $query);

                            $omim = Disease::updateOrCreate(
                                [
                                    'curie' => $query,
                                    'type' => $type,
                                    'title' => $title,
                                    'uuid'  => $uuid
                                ],
                                [
                                    'curie' => $query,
                                    'type' => $type,
                                    'title' => $title,
                                    'uuid'  => $uuid
                                ]
                            );

                            // Connect the xrefs
                            $mondo->xrefs = $omim->id;
                            $omim->xrefs =  $mondo->id;
                            $mondo->related_exactMatch = $omim->id;
                            $omim->related_exactMatch =  $mondo->id;


                            //dd($mondo);
                            //dd($omim);
                            $mondo->save();
                            $omim->save();


                            $disease_record = Disease::curie($row["disease_id"])->first();

                        }


                    } else {
                        echo "MONDO API ERROR -- '" . $query . " --- STATUS RETURNED > " . $response->getStatusCode() . "\n";
                    }
                }

            }

                // CONTINUE...

                if(isset($classification_missing) && isset($classification_record) && isset($inheritance_record) && isset($inheritance_missing) && isset($gene_record) && isset($disease_record) && isset($classification_missing) && isset($submitter_record) && isset($date_record)) {

                    //$uuid = $row["submitter_id"] . "__" . $row['submission_id'];
                    $uuid = $row["submitter_id"] . "-" . $row['hgnc_id'] . "-" . $row['disease_id'] . "-" . $row['moi_id'] . "-" . $row['classification_id'];
                    $uuid = Str::of($uuid)->replace('.', '-');
                    $uuid = Str::of($uuid)->replace(':', '_');
                    //$replaced = Str::of('(+1) 501-555-1000')->replaceMatches('/[^A-Za-z0-9]++/', '_')
                    //$uuid = Str::of($uuid)->slug('_');
                    //dd($row['date']);
                    //dd($uuid);
                    if(is_numeric($row['date'])) {
                        $date = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date']));
                    }elseif(DateTime::createFromFormat('Y-m-d' ,$row['date']) !== FALSE){
                        //dd($row['date']);
                        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $row['date']);
                        //dd($date);
                    } else {
                        $date = "";

                    }
                    //dd($row['notes'] ?? '');
                    $submission = Submission::updateOrCreate(
                        [
                        'uuid'                          => $uuid,
                        'submitted_run_date'                     => $this->submitted_run_date,
                        ],
                        [
                        'uuid'                                   => $uuid,
                        'order'                                  => $classification_record->order,
                        'submitted_run_date'                     => $this->submitted_run_date,
                        'submitted_as_submission_id'             => $row["submission_id"] ?? '',
                        'submitted_as_hgnc_id'                   => $row['hgnc_id'] ?? '',
                        'submitted_as_hgnc_symbol'               => $row['hgnc_symbol'] ?? '',
                        'submitted_as_disease_id'                => $row['disease_id'] ?? '',
                        'submitted_as_disease_name'              => $row['disease_name'] ?? '',
                        'submitted_as_moi_id'                    => $row['moi_id'] ?? '',
                        'submitted_as_moi_name'                  => $row['moi_name'] ?? '',
                        'submitted_as_submitter_id'              => $row['submitter_id'] ?? '',
                        'submitted_as_submitter_name'            => $row['submitter_name'] ?? '',
                        'submitted_as_classification_id'         => $row['classification_id'] ?? '',
                        'submitted_as_classification_name'       => $row['classification_name'] ?? '',
                        'submitted_as_date'                      => $date,
                        'submitted_as_public_report_url'         => $row['public_report_url'] ?? '',
                        'submitted_as_notes'                     => $row['notes'] ?? '',
                        'submitted_as_pmids'                     => $row['pmids'] ?? '',
                        'submitted_as_assertion_criteria_url'    => $row['assertion_criteria_url'] ?? ''
                    ]);
                            //dd($submission);
                    if ($row['submitter_id'] ?? '') {
                        $submission->submitter()->associate($submitter_record);
                    }

                    if ($row['hgnc_id'] ?? '') {
                        $submission->gene()->associate($gene_record);
                    }

                    if ($row['disease_id'] ?? '') {
                        $submission->disease()->associate($disease_record);
                        $submission->disease_original()->associate($disease_record);

                        //
                        $relate_options[$disease_record->id] = [
                            'type'          => 'original',
                            'ontology'      => $disease_record->type
                        ];
                        foreach ($disease_record->equivalents as $eqivs) {
                            $relate_options[$eqivs->id] = [
                                'type'          => 'equiv',
                                'ontology'      => $eqivs->type
                            ];
                            if($eqivs->type == "MONDO") {
                                $submission->disease()->associate($eqivs->id);
                            }
                        }

                        $submission->diseases()->sync($relate_options, false);

                    }

                    if ($row['moi_id'] ?? '') {
                        $submission->inheritance()->associate($inheritance_record);
                    } else {
                        $inheritance_record = $inheritance_missing;
                        $submission->inheritance()->associate($inheritance_missing);
                    }

                    if($row['classification_id'] ?? '') {
                        $submission->classification()->associate($classification_record);
                    } else {
                        $classification_record = $classification_missing;
                        $submission->classification()->associate($classification_missing);
                    }






                    $submission->save();





                } else {

                    if (!isset($classification_record)) {
                        echo $row["submission_id"] . " | was skipped. Bad classification | " . $row["classification_id"] . "\n";
                    }
                    if (!isset($inheritance_record)) {
                        echo $row["submission_id"] . " | was skipped. Bad inheritance | " . $row["moi_id"] . "\n";
                    }
                    if (!isset($gene_record)) {
                        echo $row["submission_id"] . " | was skipped. Bad Gene | " . $row["hgnc_id"] . "\n";
                    }
                    if (!isset($disease_record)) {
                        echo $row["submission_id"] . " | was skipped. Bad disease | " . $row["disease_id"] . "\n";
                    }
                    if (!isset($submitter_record)) {
                        echo $row["submission_id"] . " | was skipped. Bad submitter | " . $row["submitter_id"] . " \n";
                    }
                }

            } else {
                echo $rowIndex . " | was skipped. Missing info... \n";
                // if (!isset($row["classification_id"])) {
                //     echo $rowIndex . " | was skipped. Missing classification \n";
                // }
                // if (!isset($row["moi_id"])) {
                //     echo $rowIndex . " | was skipped. Missing inheritance \n";
                // }
                // if (!isset($row["hgnc_id"])) {
                //     echo $rowIndex . " | was skipped. Missing gene \n";
                // }
                // if (!isset($row["disease_id"])) {
                //     echo $rowIndex . " | was skipped. Missing disease \n";
                // }
                // if (!isset($row["submitter_id"])) {
                //     echo $rowIndex . " | was skipped. Missing submitter \n";
                // }
            }
        }
    }

    public function headingRow(): int
    {
        return 6;
    }
}
