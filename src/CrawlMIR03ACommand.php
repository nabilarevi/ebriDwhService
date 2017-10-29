<?php namespace Andriyanto\ebriDwhService;

use DB,
    XmlParser,
    Illuminate\Console\Command,
    Illuminate\Support\Facades\File as FileFacade;
use Andriyanto\ebriDwhService\ebriConnectionService,
    Andriyanto\ebriDwhService\Helpers\InsertionDwhBranch;

use Andriyanto\ebriDwhService\Eloquent\Region,
    Andriyanto\ebriDwhService\Eloquent\MainBranch,
    Andriyanto\ebriDwhService\Eloquent\Branch;

/**
 * Create a brand new package.
 *
 * @package Andriyanto
 * @author ebriDwhService
 *
 **/
class CrawlMIR03ACommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawller:MIR03' ;

    /**
     * The file configuration
     *
     * @var string
     */
    protected $config,
              $host,
              $log,
              $dates;

    protected $file,  $fileResult = false, $connection, $insertion;

    /**
     * The status connection
     *
     * @var integer
     */
    protected $http_status = 0, $http_status_download = 0;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto crawller and download report MIR03 from Porta Datawarehouse PT. Bank Rakyat Indonesia';

    /**
    * Create a new command instance.`
    *
    * @return void
    */
   public function __construct(ebriConnectionService $connection, InsertionDwhBranch $insertion)
   {
       parent::__construct();
       $this->connection        = $connection;
       $this->insertion         = $insertion;
       $this->config            = config('ebriDwhService');
       $this->setDates();
       $this->initFolder();
       $this->setLog();
   }

   /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info(\Carbon\Carbon::now()." | START      | Job DWHBranch starting");
        fwrite($this->log, print_r(\Carbon\Carbon::now()." | START      | Job DWHBranch starting\n", true));

        if( $this->connection->pingServer($this->config['localhost']) == 200 )
        {
            $this->info(\Carbon\Carbon::now()." | SUCCESS    | Web Server Status : ".$this->config['localhost']. " OK (√)");
            fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Web Server Status : ".$this->config['localhost']. " OK (√)\n", true));
            if(DB::connection()->getDatabaseName())
            {
                $this->info(\Carbon\Carbon::now()." | SUCCESS    | Database Status ".DB::connection()->getDatabaseName()." OK (√)");
                fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Database Status ".DB::connection()->getDatabaseName()." OK (√)\n", true));

                $retry = 0;

                foreach(Region::where('active',1)->get() as $regions)
                {
                    $retry = 0;
                    loop:
                        while ( ($this->http_status != 200) && ($retry < count($this->config['hosts'])))
                        {
                            $this->http_status = $this->sync->pingReportServer($this->config['hosts'][$retry]);

                            if($this->http_status != 200)
                            {
                                $this->error(\Carbon\Carbon::now()." | FAILED     | Status host DWH   : status($this->http_status) cant't connect to host : ".$this->config['hosts'][$retry]);
                                fwrite($this->log, print_r(\Carbon\Carbon::now()." | FAILED     | Status host DWH   : status($this->http_status) cant't connect to host : ".$this->config['hosts'][$retry]."\n", true));
                            }
                            else
                            {
                                $this->info(\Carbon\Carbon::now()." | SUCCESS    | Status host DWH   : ".$this->config['hosts'][$retry]." status( ".$this->http_status."(√) )");
                                fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Status host DWH   : ".$this->config['hosts'][$retry]." status( ".$this->http_status."(√) )\n", true));
                            }

                            $this->host = $this->config['hosts'][$retry];
                            $retry++;
                        }

                    if(($this->http_status != 200)) {($retry == count($this->config['hosts'])) ? $retry = 0 : $retry+1; goto loop; }

                    $sourceUrl  = $this->host.$this->config['sourceFiles']['MIR03'].'&Wilayah='.$regions->code.'&periode=29%20/%202%20/%202016'.$this->config['formatFiles']['xml'];
                    $filename  = 'MIR03_('.$regions->code.')'.$regions->name.$this->config['extension']['xml'];
                    $destFilename = $fileConfig['data_source'].'/'.$filename;

                    $try = 0;
                    while ($try !=5 && $this->fileResult == false)
                    {
                        $connection = $this->connection->download($sourceUrl, $destFilename, 300);

                        if($connection['StatusCode'] == 301 || $connection['StatusCode'] == 400 || $connection['StatusCode']== 401 || $connection['StatusCode'] == 500 || $connection['StatusCode'] == 501)
                        {
                            unlink($destFilename);
                            $this->fileResult = false;
                        }else {
                            $this->fileResult = $connection['result'];
                        }
                        $this->http_status_download = $connection['StatusCode'];
                        if($try != 0) {
                            print \Carbon\Carbon::now()." | FAILED     | trying download with host : ".$this->host." (".$this->http_status_download." $try".")\n";
                            fwrite($this->log, print_r(\Carbon\Carbon::now()." | FAILED     | trying download with host : ".$this->host." (".$this->http_status_download." $try".")\n", true));
                        }
                        $try++;
                    }

                    if($try==5) {
                        $this->http_status = 0;
                        ($retry == count($this->config['hosts'])) ? $retry = 0 : $retry+1;
                        goto loop;
                    }

                    if($connection['StatusCode'] == 200 && $this->fileResult == true)
                    {
                        $movingFile = $fileConfig['data_source_zip'].'/MIR03_'.$this->dates.'.zip';
                        $zipper->add($destFilename);
                    }
                }
            }
        }
    }

    /**
    * Execute set Folder Scheduler
    *
    * @return mixed
    */
    public function initFolder()
    {
        if ( ! FileFacade::isDirectory(storage_path($this->config['storageFile']['MIR03'])))
        {
            FileFacade::makeDirectory(storage_path($this->config['storageFile']['MIR03'], 0777, true));
        }

        if ( ! FileFacade::isDirectory(storage_path($this->config['storage']['MIR03']['data_source'])) )
        {
            FileFacade::makeDirectory(storage_path($this->config['storage']['MIR03']['data_source']), 0777, true);
        }

        if ( ! FileFacade::isDirectory(storage_path($this->config['storage']['MIR03']['data_source_zip'])) )
        {
            FileFacade::makeDirectory(storage_path($this->config['storage']['MIR03']['data_source_zip']), 0777, true);
        }

        if ( ! FileFacade::isDirectory(storage_path($this->config['storage']['MIR03']['log'])) )
        {
            FileFacade::makeDirectory(storage_path($this->config['storage']['MIR03']['log']), 0777, true);
        }
    }

    /**
    * set folder log
    *
    * @return file log
    */
    public function setLog()
    {
        $this->log = fopen(storage_path($this->config['storage']['MIR03']['log'])."/log_".$this->dates.".log", "wb");
    }

    /**
     * SetXmlParser for open xml file
     *
     * @return array
     */
    private function _setXmlParser()
    {
        $xml = XmlParser::load($this->file);
        return $xml->getContent();
    }

    /**
     * Set dates
     *
     * @return array
     */
    public function setDates()
    {
        $this->dates = '2016-01-29';
    }
}
