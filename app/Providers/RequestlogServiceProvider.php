<?php

namespace App\Providers;

use DateTime;
use App\Traits\HeadersTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class RequestlogServiceProvider extends ServiceProvider
{
    use HeadersTrait;
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // $this->publishes([
        //     __DIR__ . '/config/config.php' => config_path('config.php'),
        // ], 'config');
    }

    /**
     * Bootstrap services.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function boot(Request $request)
    {
        // dd($request);
        $logDetails = $this->prepareLogDetails($request);
        $this->sendLogToApi($logDetails);
    }

    /**
     * Prepare log details for a given HTTP request.
     *
     * This function extracts relevant details from the request and response
     * to prepare a log entry. It collects headers, fetches additional data
     * from the server, and formats them into an array.
     *
     * @param Request $request The HTTP request object containing request details.
     * @return array An array containing various details for logging.
     */
    private function prepareLogDetails(Request $request)
    {
        $headersObject = collect($request->header())->map(function ($value, $key) {
            return $value[0];
        });

        $response = Http::get(env('APP_URL'));
      
        // Log the details
        return [
            "Protocol" => $request->server("SERVER_PROTOCOL"),
            "Request_url" => $request->fullUrl(),
            "Time" => $time = (new DateTime())->format("F jS Y, h:i:s"),
            "Hostname" => gethostname(),
            "Method" => $request->method(),
            "Path" => $request->path(),
            "Status_code" => $response->getStatusCode(),
            "Status_text" => $response->getReasonPhrase(),
            "IP_Address" => $request->ip(),
            "Memory_usage" => round(memory_get_usage(true) / (1024 * 1024), 2) . " MB",
            "User-agent" => $request->header("user-agent"),
            "HEADERS" => $headersObject,
        ];
    }

    /**
     * Send log details to the API for storage.
     *
     * This function prepares a structured payload containing various log details
     * and sends it to the specified API endpoint for storage. It constructs the
     * payload based on the provided log details and additional metadata.
     *
     * @param array $logDetails An array containing various log details to be sent to the API.
     * @return void
     */
    private function sendLogToApi($logDetails)
    {
        $body = [
            "request_user_agent" => $logDetails["User-agent"],
            "request_host" => $logDetails["HEADERS"]->get('host'),
            "request_url" => $logDetails["Request_url"],
            "request_method" => $logDetails["Method"],
            "status_code" => $logDetails["Status_code"],
            "status_message" => $logDetails["Status_text"],
            "requested_at" => $logDetails["Time"],
            "request_ip" => $logDetails["IP_Address"],
            "response_message" => "Project created successfully",
            "protocol" => $logDetails["Protocol"],
            "payload" => "Payload",
            "tag" => env('TAG'),
            "meta" => [
                "Hostname" => gethostname(),
                "Path" => $logDetails["Path"],
                "Memory_usage" => $logDetails["Memory_usage"],
                "HEADERS" => $logDetails["HEADERS"]->toArray(),
            ],
        ];
        $response = $this->processApiResponse("/api/logs", $body);
    }
}
