<?php

namespace ZendeskSlackUnfurl;

class ZendeskClient
{
    public function __construct($domain, $username, $password, $scheme = 'https')
    {
        $this->base_url = "$scheme://$domain/api/v2";
        $this->auth_string = base64_encode("$username:$password");
    }

    private function getContext()
    {
        return stream_context_create([
            'http' => [
                'header' => [
                    'Authorization: Basic ' . $this->auth_string,
                    'Content-type: application/json',
                ]
            ]
        ]);
    }

    private function request($resource, $id = null)
    {
        $url = sprintf('%s/%s/%s', $this->base_url, $resource, $id);
        $response = file_get_contents($url, false, $this->getContext());

        return json_decode($response, true);
    }

    public function getTicket($id)
    {
        $ticket = $this->request('tickets', $id);

        return $ticket[array_keys($ticket)[0]];
    }
}