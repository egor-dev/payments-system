<?php

/**
 * Возвращает деньги в читабельном формате "1 234,00 $".
 * Хоть в разных валютах формат может меняться (доллар пишется сначала),
 * но закроем на это глаза.
 *
 * @param $amount
 * @param string $currencySign
 *
 * @return string
 */
function money_output($amount, $currencySign)
{
    return number_format($amount, 2, ',', ' ') . ' ' . $currencySign;
}
