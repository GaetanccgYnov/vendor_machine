<?php
namespace App;

enum CoinCode: int
{
    case ONE_CENT = 1;
    case TWO_CENTS = 2;
    case FIVE_CENTS = 5;
    case TEN_CENTS = 10;
    case TWENTY_CENTS = 20;
    case FIFTY_CENTS = 50;
    case ONE_EURO = 100;
    case TWO_EUROS = 200;
}
