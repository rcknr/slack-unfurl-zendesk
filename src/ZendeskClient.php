<?php

namespace ZendeskSlackUnfurl;

/**
 * @method getTicket($id)
 * @method getTicketFields()
 * @method getUser($id)
 */
class ZendeskClient
{
    public function __construct($base_url, $username, $password)
    {
        $this->base_url = $base_url;
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

    private function request($resource, $arguments = [])
    {
        $url = sprintf('%s/%s/%s', $this->base_url, $resource, reset($arguments));

        if ($response = file_get_contents($url, false, $this->getContext())) {
            return json_decode($response, true);
        }

        return null;
    }

    public function __call($function, $arguments)
    {
        if (preg_match('/^get(?P<name>[A-Z]\w+)/', $function, $m)) {
            $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $m['name']));
            $name .= $arguments ? 's' : null;

            if ($resource = $this->request($name, $arguments)) {
                return reset($resource);
            }
        }

        return null;
    }
}
