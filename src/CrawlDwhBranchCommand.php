<?php namespace Andriyanto\ebriDwhService;

use DB,
    XmlParser,
    Illuminate\Console\Command,
    Illuminate\Support\Facades\File as FileFacade;
use Andriyanto\ebriDwhService\ebriConnectionService,
    Andriyanto\ebriDwhService\Helpers\InsertionDwhBranch;

/**
 * Create a brand new package.
 *
 * @package Andriyanto
 * @author ebriDwhService
 *
 **/
class CrawlDwhBranchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawller:DwhBranch' ;

    /**
     * The file configuration
     *
     * @var string
     */
    protected $config,
              $host,
              $log;

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
    protected $description = 'Auto crawller and download report DWH BRANCH from Porta Datawarehouse PT. Bank Rakyat Indonesia';

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
                loop:
                    while ( ($this->http_status != 200) && ($retry < count($this->config['hosts'])))
                    {
                        $this->http_status = $this->connection->pingReportServer($this->config['hosts'][$retry]);

                        if($this->http_status != 200)
                        {
                            $this->error(\Carbon\Carbon::now()."    | FAILED     | Status host DWH   : status($this->http_status) cant't connect to host : ".$this->config['hosts'][$retry]);
                            fwrite($this->log, print_r(\Carbon\Carbon::now()."    | FAILED     | Status host DWH   : status($this->http_status) cant't connect to host : ".$this->config['hosts'][$retry]."\n", true));
                        }
                        else
                        {
                            $this->info(\Carbon\Carbon::now()."    | SUCCESS    | Status host DWH   : ".$this->config['hosts'][$retry]." status( ".$this->http_status."(√) )");
                            fwrite($this->log, print_r(\Carbon\Carbon::now()."    | SUCCESS    | Status host DWH   : ".$this->config['hosts'][$retry]." status( ".$this->http_status."(√) )\n", true));
                        }

                        $this->host = $this->config['hosts'][$retry];
                        $retry++;
                    }

                if(($this->http_status != 200)) {($retry == count($this->config['hosts'])) ? $retry = 0 : $retry+1; goto loop; }

                $sourceUrl = $this->host.$this->config['sourceFiles']['dwh_branch'].$this->config['formatFiles']['xml'];
                $basePath  = storage_path($this->config['storageFile']['dwh_branch']);
                $filename  = 'dwh_branch'.$this->config['extension']['xml'];
                $destFilename = $basePath.'/'.$filename;

                $try = 0;
                while ($try !=20 && $this->fileResult == false)
                {
                    $this->info(\Carbon\Carbon::now()."    | INFO       | Dowloading DWH Branch : ".$this->host);
                    fwrite($this->log, print_r(\Carbon\Carbon::now()."    | INFO       | Dowloading DWH Branch : ".$this->host."\n", true));

                    $bar = $this->connection->barSetup($this->output->createProgressBar(1));

                    $connection = $this->connection->download($sourceUrl = 'http://andriyanto.co/dwh_branch.xml', $destFilename, $bar, 300);


                    $this->http_status_download = $connection['StatusCode'];
                    $this->fileResult = $connection['result'];
                    if($try != 0)
                    {
                        $this->error(\Carbon\Carbon::now()."    | FAILED     | Trying another host : ".$this->host." (".$this->http_status_download." $try".")");
                        fwrite($this->log, print_r(\Carbon\Carbon::now()."    | FAILED     | Trying another host : ".$this->host." (".$this->http_status_download." $try".")\n", true));
                    }
                    $try++;
                }
                if($try==5)
                {
                    unlink($destFilename);
                    $this->http_status = 0;
                    ($retry == count($this->config['hosts'])) ? $retry = 0 : $retry+1;
                    goto loop;
                }

                if($connection['StatusCode'] == 200 && $this->fileResult == true)
                {
                    ini_set('memory_limit', '4096M');
                    $this->file     = $destFilename;
                    $xml            = $this->_setxmlParser($this->file);

                    if(!$xml) goto loop;

                    $this->output->newLine(1);
                    $parserRegion   = $this->insertion->_parserRegion($xml, $this->log);
                    if($parserRegion === TRUE)
                    {
                        $this->info(\Carbon\Carbon::now()." | SUCCESS    | Parser Region Success\n\n");
                        fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Parser Region Success..\n\n", true));
                    }

                    $parserMBranch   = $this->insertion->_parserMBranch($xml, $this->log);
                    if($parserMBranch === TRUE)
                    {
                        $this->info(\Carbon\Carbon::now()." | SUCCESS    | Parser Main Branch Success\n\n");
                        fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Parser Main Branch Success..\n\n", true));
                    }

                    $parserBranch   = $this->insertion->_parserBranch($xml, $this->log);
                    if($parserBranch === TRUE)
                    {
                        $this->info(\Carbon\Carbon::now()." | SUCCESS    | Parser Branch Success\n\n");
                        fwrite($this->log, print_r(\Carbon\Carbon::now()." | SUCCESS    | Parser Branch Success..\n\n", true));
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
        if ( ! FileFacade::isDirectory(storage_path($this->config['storageFile']['dwh_branch'])))
        {
            FileFacade::makeDirectory(storage_path($this->config['storageFile']['dwh_branch'], 0777, true));
        }
    }

    /**
    * set folder log
    *
    * @return file log
    */
    public function setLog()
    {
        $this->log = fopen(storage_path($this->config['storageFile']['dwh_branch'])."/log_".date('m-d-Y_hia').".log", "wb");
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
}
