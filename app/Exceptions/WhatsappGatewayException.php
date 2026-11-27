<?php

namespace App\Exceptions;

use RuntimeException;

/** Gateway WhatsApp gagal mengirim (jaringan, status HTTP gagal, atau penolakan penyedia). */
class WhatsappGatewayException extends RuntimeException {}
