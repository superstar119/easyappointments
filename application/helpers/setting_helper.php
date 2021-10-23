<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

if ( ! function_exists('setting'))
{
    /**
     * Get / set the specified setting value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * Example "Get":
     *
     * $company_name = session('company_name', FALSE);
     *
     * Example "Set":
     *
     * setting(['company_name' => 'ACME Inc']);
     *
     * @param array|string $key
     * @param mixed $default
     *
     * @return mixed|NULL Returns the requested value or NULL if you assign a new setting value. 
     *
     * @throws InvalidArgumentException
     */
    function setting($key = NULL, $default = NULL)
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        if (empty($key))
        {
            throw new InvalidArgumentException('The $key argument cannot be empty.');
        }

        if (is_array($key))
        {
            foreach ($key as $item => $value)
            {
                $CI->session->set_userdata($item, $value);
            }

            return NULL;
        }

        $value = $CI->session->userdata($key);

        return $value ?? $default;
    }
}
