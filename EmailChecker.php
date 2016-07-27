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
        return !in_array($domain, $domains);
    }

    public function getDomains()
    {
        return json_decode(file_get_contents(__DIR__ . '/blacklist.json'));

    }
}