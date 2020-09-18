<?php
declare(strict_types = 1);

namespace AdsSquared\Classes;

use \DateTime;
use \Exception;

use AdsSquared\Messages;

class DailyReport extends GenericReport implements \AdsSquared\Interfaces\Report
{
    /**
     * @property DateTime
     */
    protected $startDate;

    /**
     * @property DateTime
     */
    protected $endDate;

    /**
     * @property string - Restrict results to specified market. Optional.
     */
    protected $market = '';

    /**
     * @param string $jobName
     * @param string $username
     * @param string $apiKey
     * @param string $startDate
     * @param string $endDate
     * @param string $market
     * @param int $timeout
     */
    public function __construct(
        string $jobName,
        string $username,
        string $apiKey,
        string $startDate, 
        string $endDate,
        string $market = null,
        int $timeout = 3000
    ) {
        $this->jobName = $jobName;
        $this->username = $username;
        $this->token = base64_encode("{$username}:{$apiKey}");
        
        $this->startDate = new DateTime($startDate);
        $this->endDate = new DateTime($endDate);
        $this->timeout = $timeout;

        if (!empty($market)) {
            $this->market = $market;
        }

        if (empty($this->startDate)) {
            throw new Exception(Messages::INVALID_START_DATE);
        }

        if (empty($this->endDate)) {
            throw new Exception(Messages::INVALID_END_DATE);
        }
        
        if ($this->startDate > $this->endDate) {
            throw new Exception(Messages::INCORRECT_DATE_ORDER);
        }

        $payload = [
            'date_start' => $this->startDate->format('Y-m-d'),
            'date_end' => $this->endDate->format('Y-m-d'),
        ];

        if (!empty($this->market)) {
            $payload['mkt'] = $this->market;
        }

        $this->payload = $payload;

        // Build the client and set up basic auth
        $this->client = new \GuzzleHttp\Client(['base_uri' => $this->baseURI]);

        $this->startReport();
    }

    /**
     * Begin generating the report
     *
     * @return void
     */
    public function startReport() {
        $response = $this->makeRequest("create/{$this->jobName}");
        $decoded = json_decode((string)$response->getBody()->getContents(), true);

        if (!is_array($decoded)) {
            throw new Exception(Messages::UNEXPECTED_RESPONSE . $response->getBody()->getContents());
        }

        $this->jobStatus = 'Requested';
        $this->jobID = $decoded['payload']['jobid'];
    }
}
