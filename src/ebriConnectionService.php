<?php namespace Andriyanto\ebriDwhService;

use Ping;
use GuzzleHttp\Client;

class ebriConnectionService
{
    public function pingServer($localhost)
    {
        return Ping::check($localhost);
    }

    public function pingReportServer($host)
    {
        return Ping::check($host);
    }

    public function download($sourceUrl, $destinationPath, $timeout)
    {
        $ch = curl_init($sourceUrl);
        $fp = fopen($destinationPath, "wb");

        $options = array(
            CURLOPT_FILE => $fp,
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_URL => $sourceUrl,
            CURLOPT_TIMEOUT => $timeout
        );

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        return [
            'fp'=> $fp,
            'sourceUrl' => $sourceUrl,
            'result' => $result,
            'status' => $status
        ];
    }

    public function download1($sourceUrl, $destinationPath, $timeout)
    {
        $client = new Client(
            [
                'verify' => env('CURL_VERIFY', true),
                'connect_timeout'   => $timeout
            ]
        );

        $response = $client->get($sourceUrl);
        file_put_contents($destinationPath, $response->getBody());

        if($response->getStatusCode() === 200)
        {
            return [
                'result' => true,
                'statusBody' => $response->getBody(),
                'StatusCode' => $response->getStatusCode(),
            ];
        }
    }

    /**
     * Setting custom formatting for the progress bar.
     *
     * @param  object $bar Symfony ProgressBar instance
     *
     * @return object $bar Symfony ProgressBar instance
     */
    public function barSetup($bar)
    {
        // the finished part of the bar
        $bar->setBarCharacter('<comment>=</comment>');

        // the unfinished part of the bar
        $bar->setEmptyBarCharacter('-');

        // the progress character
        $bar->setProgressCharacter('>');

        // the 'layout' of the bar
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% ');

        return $bar;
    }
}
