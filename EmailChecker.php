<?php

namespace Hostinger;

class EmailChecker
{
    private $timeout = 2;

    public function setTimeout($value)
    {
        $this->timeout = $value;
    }

    /**
     * Check if it's a valid email, ie. not a throwaway email.
     *
     * @param string $email The email to check
     *
     * @return bool true for a throwaway email
     */
    public function isValid($email)
    {
        if (strlen($email) > 254) {
            return false;
        }

        if (false === $email = filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            list($local, $domain) = explode('@', $email);
        } catch (\Exception $e) {
            return false;
        }

        $domains = $this->getDomains();
        if (in_array($domain, $domains)) {
            return false;
        }

        $mxs = $this->getMxsRecords($domain);
        if (empty($mxs)) {
            return false;
        }

        $mx_info = $mxs[array_rand($mxs)];
        $mx_host = $mx_info['target'];
        if (in_array($mx_host, $domains)) {
            return false;
        }
        $mx = substr($mx_host, ($pos = strpos($mx_host, '.')) !== false ? $pos + 1 : 0);
        if (in_array($mx, $domains)) {
            return false;
        }

        return true;
    }

    public function getDomains()
    {
        return json_decode(file_get_contents(__DIR__ . '/blacklist.json'));
    }

    public function getMxsRecords($domain)
    {
        if ($this->execEnabled()) {
            return $this->getMxRecordWithDigCmd($domain);
        }
        return dns_get_record($domain, DNS_MX);

    }

    public function execEnabled()
    {
        if (!function_exists('exec')) {
            return false;
        }
        $disabled = explode(',', ini_get('disable_functions'));
        return !in_array('exec', $disabled);
    }

    /**
     * Use shell command dig to get MX record of provided domain
     * @param string $domain
     * @return array - same return values as dns_get_record()
     * @see dns_get_record()
     */
    public function getMxRecordWithDigCmd($domain)
    {
        $lines = [];
        $command = 'dig +noall +answer +time=' . escapeshellarg($this->timeout) . ' MX ' . escapeshellarg($domain);
        exec($command, $lines);
        $output = [];
        if (empty($lines)) {
            return $output;
        }

        foreach ($lines as $line) {
            $mxInfo = explode(' ', preg_replace('!\s+!', ' ', $line));
            $output[] = [
                'host' => $mxInfo[0],
                'ttl' => $mxInfo[1],
                'class' => $mxInfo[2],
                'type' => $mxInfo[3],
                'pri' => $mxInfo[4],
                'target' => $mxInfo[5],
            ];
        }
        return $output;
    }
}
