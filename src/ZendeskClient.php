<?php

namespace ZendeskSlackUnfurl;

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

    private function request($resource, $id = null)
    {
        $resource = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $resource));
        $url = sprintf('%s/%ss/%s', $this->base_url, $resource, $id);

        if ($response = file_get_contents($url, false, $this->getContext())) {
            return json_decode($response, true);
        }

        return null;
    }

    public function __call($name, $arguments)
    {
        preg_match('/^get(?P<resource>[A-Z]\w+)/', $name, $m);

        if ($m && $arguments) {
            if ($resource = $this->request($m['resource'], $arguments[0])) {
                return $resource[array_keys($resource)[0]];
            }
        }

        return null;
    }
}
