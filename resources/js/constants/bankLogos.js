// Canonical bank-key → logo URL map.
// Keys MUST match the bank keys returned by `detectBankKey` /
// `detectBankFromBin` (see resources/js/composables/useCardBranding.js).
//
// Banks without a bundled logo asset (gib, stc, enbd, barraq) are intentionally
// omitted — callers should fall back to no logo / a generic placeholder.

const _alrajhi      = new URL('../../images/logo/banks/alrajhi.png', import.meta.url).href;
const _snb          = new URL('../../images/logo/banks/SNB.png', import.meta.url).href;
const _alinma       = new URL('../../images/logo/banks/alinma.png', import.meta.url).href;
const _sabb         = new URL('../../images/logo/banks/SABB.png', import.meta.url).href;
const _aljazira     = new URL('../../images/logo/banks/Bank_Aljazira.png', import.meta.url).href;
const _riyad        = new URL('../../images/logo/banks/Riyad_Bank.png', import.meta.url).href;
const _albilad      = new URL('../../images/logo/banks/Bank_Albilad.png', import.meta.url).href;
const _anb          = new URL('../../images/logo/banks/anb.png', import.meta.url).href;
const _saib         = new URL('../../images/logo/banks/Saudi_Investment.png', import.meta.url).href;
const _bsf          = new URL('../../images/logo/banks/Saudi_Fransi_Capital.webp', import.meta.url).href;

export const BANK_LOGOS = {
  // canonical bank keys (single source of truth)
  rajhi:  _alrajhi,
  ahli:   _snb,
  inma:   _alinma,
  sabb:   _sabb,
  jazira: _aljazira,
  riyad:  _riyad,
  bilad:  _albilad,
  anb:    _anb,
  saib:   _saib,
  bsf:    _bsf,

  // legacy filename-style aliases (kept for backward compatibility with
  // existing callers that pass the raw filename keyword instead of a key).
  alinma:               _alinma,
  alrajhi:              _alrajhi,
  Bank_Albilad:         _albilad,
  Bank_Aljazira:        _aljazira,
  Riyad_Bank:           _riyad,
  SABB:                 _sabb,
  Saudi_Fransi_Capital: _bsf,
  Saudi_Investment:     _saib,
  SNB:                  _snb,
};

// Backward-compatible alias for existing lowercase imports
export const bankLogos = BANK_LOGOS;
