<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Enum;

enum StatusCode: string
{
    // 200 OK
    case VALID = 'evatr-0000';
    case VALID_FUTURE = 'evatr-2002';
    case VALID_PAST = 'evatr-2006';
    case VALID_QUALIFIED_SPECIAL = 'evatr-2008';

    // 400 Bad Request
    case FIELD_MISSING = 'evatr-0002';
    case OWN_VAT_ID_INVALID = 'evatr-0004';
    case FOREIGN_VAT_ID_INVALID = 'evatr-0005';
    case MAX_QUERIES_REACHED = 'evatr-0008';
    case FOREIGN_VAT_ID_SCHEMA_MISMATCH = 'evatr-0012';
    case INVALID_COUNTRY_CODE = 'evatr-2003';

    // 403 Forbidden
    case UNAUTHORIZED = 'evatr-0006';
    case FAULTY_CALL = 'evatr-0007';

    // 404 Not Found
    case FOREIGN_VAT_ID_NOT_ALLOCATED = 'evatr-2001';
    case OWN_VAT_ID_NOT_VALID = 'evatr-2005';

    // 500 Internal Server Error
    case PROCESSING_UNAVAILABLE_2004 = 'evatr-2004';
    case PROCESSING_UNAVAILABLE_2011 = 'evatr-2011';
    case PROCESSING_UNAVAILABLE_3011 = 'evatr-3011';

    // 503 Service Unavailable
    case SERVICE_UNAVAILABLE_0011 = 'evatr-0011';
    case SERVICE_UNAVAILABLE_1001 = 'evatr-1001';
    case SERVICE_UNAVAILABLE_1002 = 'evatr-1002';
    case SERVICE_UNAVAILABLE_1003 = 'evatr-1003';
    case SERVICE_UNAVAILABLE_1004 = 'evatr-1004';
}
