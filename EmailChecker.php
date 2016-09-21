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
        if(in_array($domain, $domains)) {
            return false;
        }

        $mxs = dns_get_record($domain, DNS_MX);
        if (is_array($mxs)) {
            $mx_info = $mxs[array_rand($mxs)];
            $mx_host = $mx_info['target'];
            $mx = substr($mx_host, ($pos = strpos($mx_host, '.')) !== false ? $pos + 1 : 0);
            if(in_array($mx, $domains)) {
                return false;
            }
        }

        return true;
    }

    public function getDomains()
    {
        return json_decode(file_get_contents(__DIR__ . '/blacklist.json'));

    }
}