# Delete all currencies except MAD, USD, EUR and GBP
DELETE FROM llx_c_currencies WHERE code_iso not in ("MAD", "USD", "EUR", "GBP");