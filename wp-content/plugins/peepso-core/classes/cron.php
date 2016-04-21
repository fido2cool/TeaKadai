<?php

class PeepSoCron 
{
	public static function initialize()
	{
PeepSo::log(__METHOD__.'() has been called');

		if(0==PeepSo::get_option('disable_mailqueue', 0)) {
			add_action(PeepSo::CRON_MAILQUEUE, array('PeepSoMailQueue', 'process_mailqueue'));
		}

		add_action(PeepSo::CRON_DAILY_EVENT, array('PeepSoActivityPurge', 'purge_activity_items'));
		add_action(PeepSo::CRON_DAILY_EVENT, array('PeepSoDataPurge', 'purge_notification_items'));
		do_action('peepso_cron_init');
	}
}

// EOF