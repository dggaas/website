<?php
namespace Destiny\Commerce;

abstract class PaymentProfileStatus {

    const _NEW = 'New';
    const ERROR = 'Error';
    const ACTIVE_PROFILE = 'ActiveProfile';
    const CANCELLED_PROFILE = 'CancelledProfile';
    const SKIPPED = 'Skipped';

}