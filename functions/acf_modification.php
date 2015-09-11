<?php
// ajout des pages d'options
if (function_exists('acf_add_options_page')) {
	acf_add_options_page('Name of the option page');
	acf_add_options_sub_page('sub page 1');
	acf_add_options_sub_page('sub page 2');
}