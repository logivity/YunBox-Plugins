<?php

	namespace WP_Piwik\Admin;

	class Frame extends \WP_Piwik\Admin {

		public function show() {
			echo '<iframe src="' . self::$settings->getGlobalOption ( 'piwik_url' ) . '/index.php?module=Widgetize&action=iframe&moduleToWidgetize=Dashboard&actionToWidgetize=index&idSite=' . self::$settings->getOption ( 'site_id' ) . '&period=week&date=yesterday" frameborder="0" marginheight="0" marginwidth="0" width="100%" style="height:90vh;"></iframe>';
			//echo '<iframe id="thisframe" src="' . self::$settings->getGlobalOption ( 'piwik_url' ) . '/index.php?module=Login&action=logme&login=' . self::$settings->getGlobalOption ( 'piwik_account_name' ) . '&password=' . md5(self::$settings->getGlobalOption ( 'piwik_account_pwd' )) . '&idSite=' . self::$settings->getOption ( 'site_id' ) . '" style="width: 100%;border-width: 0;height:768px;" scrolling="auto"></iframe>';
		}

		public function printAdminScripts() {
		}

		public function extendAdminHeader() {
		}
	}
