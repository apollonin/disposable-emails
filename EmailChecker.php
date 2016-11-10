<?php

namespace Hostinger;

class EmailChecker
{
    /**
     * Check if it's a valid email, ie. not a throwaway email.
     *
     * @param string $email The email to check
     *
     * @return bool true for a throwaway email
     */
    public function isValid($email)
    {
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
        if ($this->exec_enabled()) {
            exec("dig +noall +answer MX " . escapeshellarg($domain), $lines);
            $output = [];
            if (empty($lines)) {
                return $output;
            }
            foreach ($lines as $line) {
                $mxInfo   = explode(' ', preg_replace('!\s+!', ' ', $lines[0]));
                $output[] = [
                    'host'   => $mxInfo[0],
                    'ttl'    => $mxInfo[1],
                    'class'  => $mxInfo[2],
                    'type'   => $mxInfo[3],
                    'pri'    => $mxInfo[4],
                    'target' => $mxInfo[5],
                ];
            }
            return $output;
        }
        return dns_get_record($domain, DNS_MX);

    }

    function exec_enabled()
    {
        $disabled = explode(',', ini_get('disable_functions'));
        return !in_array('exec', $disabled);
    }
}