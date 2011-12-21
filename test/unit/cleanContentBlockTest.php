<?php require_once(dirname(__FILE__).'/../bootstrap/unit.php');

$t = new lime_test(2);

$value = '<p>' . html_entity_decode('&#160;') . '</p>'; // string with unencoded &nbsp;

$t->diag('Testing ContentBlockType::cleanContent($value)');

$secondValue = trim(html_entity_decode(strip_tags($value)));

$t->is($secondValue, html_entity_decode('&#160;'), 'Value is a non-breaking space - normal html decode, trim and strip_tags isn\'t enough');

$thirdValue = ContentBlockType::cleanContent($value);

$t->is($thirdValue, '', 'Value is empty using ContentBlockType::cleanContent($value)');

