<?php namespace Andriyanto\ebriDwhService\Helpers;


use Andriyanto\ebriDwhService\Eloquent\Region,
    Andriyanto\ebriDwhService\Eloquent\MainBranch,
    Andriyanto\ebriDwhService\Eloquent\Branch;

/**
 * Connection Service
 *
 * @package ebriDwhService
 * @author Andriyanto
 *
 **/
class InsertionDwhBranch
{
    protected $log;
    /**
     * Reader xml and parser region to database.`
     *
     * @return boolean
     */
    public function _parserRegion($xml, $log)
    {
        $this->log = $log;
        $STATUS_INSERT_DB = FALSE;
        print(\Carbon\Carbon::now()." | JOB        | JOB Region Started\n");
        fwrite($this->log, print_r(\Carbon\Carbon::now()." | JOB        | JOB Region Started\n", true));
        foreach($xml->table1->Detail_Collection->Detail as $dt)
        {
            $region = $dt['REGION'];
            $unique = Region::where('code', $region)->get();

            if($region != "0" and count($unique) == 0  and $region != 'S' and $region != 'T' and $region != 'U' and $region != 'V' and $region != 'Y') {
                $insert = Region::insert(array(
                    'code'  => $region,
                    'name'  => trim($dt['RGDESC']),
                    'created_at' =>  \Carbon\Carbon::now(), # \Datetime()
                    'updated_at' => \Carbon\Carbon::now(),  # \Datetime()
                ));
                $STATUS_INSERT_DB = TRUE;

                print(\Carbon\Carbon::now()." | SUCCESS    | "."Region :".$region." -- ".$dt['RGDESC']." Parser to database\n");
                fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | "."Region :".$region." -- ".$dt['RGDESC']." Parser to database\n", true));
            }
        }
        return $STATUS_INSERT_DB;
    }

    /**
    * Reader xml and parser Main Branch to database.`
    *
    * @return boolean
    */
   public function _parserMBranch($xml, $log)
   {
       $i=1;
       $this->log = $log;
       $STATUS_INSERT_DB = FALSE;
       print(\Carbon\Carbon::now()." | JOB        | JOB Main Branch Started\n");
       fwrite($this->log, print_r(\Carbon\Carbon::now()." | JOB        | JOB Main Branch Started\n", true));
       foreach($xml->table1->Detail_Collection->Detail as $dt)
       {
           $region      = $dt['REGION'];
           $main_branch = $dt['MAINBR'];
           $flags       = $dt['BRUNIT'];
           $unique = MainBranch::where('code', $region)->where('id', $main_branch)->get();

           if($region != "0" and $flags="B" and count($unique) == 0 and $region != 'S' and $region != 'T' and $region != 'U' and $region != 'V' and $region != 'Y') {
               $STATUS_INSERT_DB = TRUE;
               $insert = MainBranch::insert(array(
                   'code'  => $region,
                   'id'    => $main_branch,
                   'name'  => trim($dt['MBDESC']),
                   'description'  => '',
                   'order' => $i+=1,
                   'created_at' =>  \Carbon\Carbon::now(), # \Datetime()
                   'updated_at' => \Carbon\Carbon::now(),  # \Datetime()
               ));

               print(\Carbon\Carbon::now()." | SUCCESS    | Region : (".$region.") -- MB ".$main_branch." -- ".$dt['BRDESC']."\n");
               fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Region : (".$region.") -- MB ".$main_branch." -- ".$dt['BRDESC']."\n", true));
           }
       }
       return $STATUS_INSERT_DB;
   }

   /**
     * Reader xml and parser Branch to database.`
     *
     * @return boolean
     */
    public function _parserBranch($xml, $log)
    {
        $initiateBranch = [];
        $initiateMainBranch = [];
        $initiateDescripiton = [];
        $this->log = $log;

        print(\Carbon\Carbon::now()." | JOB        | JOB Branch Started\n");
        fwrite($this->log, print_r(\Carbon\Carbon::now()." | JOB        | JOB Branch Started\n", true));
        foreach(Region::all() as $regions)
        {
            $STATUS_INSERT_DB = FALSE;
            if($regions->code != "0" && $regions->code != "S" && $regions->code != "T" && $regions->code != "U" && $regions->code != "V" && $regions->code != "Y")
            {
                $branches = Branch::where('code', $regions->code)->get();
                foreach($branches as $branch_code) {
                    if($regions->code == $branch_code->code) {
                        $initiateBranch[] = $branch_code->id;
                        $initiateDescripiton[$branch_code->id] = $branch_code->description;
                        $initiateMainBranch[$branch_code->id] = $branch_code->mbranch;
                    }
                }

                foreach($xml->table1->Detail_Collection->Detail as $dt)
                {
                    if($regions->code == $dt['REGION'])
                    {
                        $region      = $dt['REGION'];
                        $main_branch = $dt['MAINBR'];
                        $branch      = $dt['BRANCH'];
                        $flags       = $dt['BRUNIT'];
                        $description = trim($dt['BRDESC']);

                        if(!in_array($branch, $initiateBranch))
                        {
                            $STATUS_INSERT_DB = TRUE;
                            $data[$regions->code][] = array(
                                'code'      => $region,
                                'mbranch'   => $main_branch,
                                'id'        => $branch,
                                'segment'   => ( $flags == "U" ) ? 2 : 1,
                                'flags'     => $this->setFlags($flags, substr($dt['BRDESC'], 0, 10)),
                                'description'=> $description,
                                'created_at' => \Carbon\Carbon::now(), # \Datetime()
                                'updated_at' => \Carbon\Carbon::now(),  # \Datetime()
                            );
                            print(\Carbon\Carbon::now()." | SUCCESS    | Unit Kerja Baru :     Region : (".$region.") -- MB".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n");
                            fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Unit Kerja Baru :     Region : (".$region.") -- MB".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n", true));
                        }else
                        {
                            /* Update Unit Kerja */
                            if (isset($initiateDescripiton[intval($branch)]) and $initiateDescripiton[intval($branch)] != $description)
                            {
                                $update_uker = array(
                                    'code'      => $region,
                                    'mbranch'   => $main_branch,
                                    'id'        => $branch,
                                    'segment'   => ( $flags == "U" ) ? 2 : 1,
                                    'flags'     => $this->setFlags($flags, substr($dt['BRDESC'], 0, 10)),
                                    'description'=> $description
                                );
                                Branch::where('code', $region)->where('mbranch', $initiateMainBranch[intval($branch)])->where('id', $branch)
                                    ->update($update_uker);

                                if($initiateMainBranch[intval($branch)] == $main_branch) {
                                    print(\Carbon\Carbon::now()." | UPDATE     | Update Uker nama uker = ".$branch. " (".$initiateDescripiton[intval($branch)].")       -->     Region : (".$region.") -- MB: ".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n");
                                    fwrite($this->log, print_r(Carbon\Carbon::now()." | UPDATE     | Update Uker nama uker = ".$branch. " (".$initiateDescripiton[intval($branch)].")       -->     Region : (".$region.") -- MB: ".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n", true));
                                }else
                                {
                                    print(\Carbon\Carbon::now()." | UPDATE     | Resupervisi Uker nama uker = ".$branch. " (".$initiateDescripiton[intval($branch)].")    -->    Region : (".$region.") -- MB: ".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n");
                                    fwrite($this->log, print_r(\Carbon\Carbon::now()." | UPDATE     | Resupervisi Uker nama uker = ".$branch. " (".$initiateDescripiton[intval($branch)].")    -->    Region : (".$region.") -- MB: ".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n", true));
                                }
                            }
                            /* Resupervisi Unit Kerja
                            ** Perpindahan unit kerja ke cabang lain */
                            else if (isset($initiateMainBranch[intval($branch)]) and $initiateMainBranch[intval($branch)] != $main_branch)
                            {
                                $resupervisi = array(
                                    'code'      => $region,
                                    'mbranch'   => $main_branch,
                                    'id'    => $branch,
                                    'segment'   => ( $flags == "U" ) ? 2 : 1,
                                    'flags'     => $this->setFlags($flags, substr($dt['BRDESC'], 0, 10)),
                                    'description'=> $description
                                );
                                Branch::where('code', $region)->where('mbranch', $initiateMainBranch[intval($branch)])->where('id', $branch)
                                                ->update($resupervisi);

                                print(\Carbon\Carbon::now()." | UPDATE     | Resupervisi = ".$initiateMainBranch[intval($branch)]."     -->     Region : (".$region.") -- MB: ".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n");
                                fwrite($this->log, print_r(\Carbon\Carbon::now()." | UPDATE     | Resupervisi = ".$initiateMainBranch[intval($branch)]."     -->     Region : (".$region.") -- MB: ".$main_branch." -- B (".$branch.") ".$dt['BRDESC']."\n", true));
                            }
                        }
                    }
                }
                $initiateMainBranch = []; $initiateDescripiton = [];
                if($STATUS_INSERT_DB)
                {
                    Branch::insert($data[$regions->code]);
                    $initiateBranch = [];
                }
                else {
                    print(\Carbon\Carbon::now()." | NO UPDATE  | Tidak ada Resupervisi Unit Kerja Kanwil\n");
                    fwrite($this->log, print_r(\Carbon\Carbon::now()." | NO UPDATE  | Tidak ada Resupervisi Unit Kerja Kanwil\n", true));
                }
            }
        }
        return $STATUS_INSERT_DB;
    }

    /**
    * Check Status Flags of branches.`
    *
    * @return integer
    */
   private function setFlags($flags, $name)
   {
       if($flags == 'B' && $name != 'VENDOR CRO') return 1;
       elseif($flags == 'S') return 3;
       elseif($flags == 'K') return 2;
       elseif($flags == 'U') return 4;
       elseif($flags == 'B' && $name == 'VENDOR CRO') return 5;
       elseif($flags == 'B' && $name == 'VENDOR CRO') return 5;
   }
}
