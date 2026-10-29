<?php
declare(strict_types=1);
// Retired in 0.8.1-dev.12: intentionally no standalone CRUD or data mutation.
Session::checkCentralAccess();
Html::displayNotFoundError();
