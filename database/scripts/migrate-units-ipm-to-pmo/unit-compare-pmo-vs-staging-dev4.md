# Perbandingan satuan: PMO (`oms_product_unit`) vs IPM DEV (`units`)

- **Sumber PMO:** `u906028329_pmo_pegasus.sql`
- **IPM STAGING:** `u906028329_dev (4).sql` (26 Sep 2026) — dump utuh
- **Catatan:** dump DEV (3) sebelumnya **rusak** (permission denied) — jangan dipakai
- **Aturan:** PMO = sumber utama untuk sync/merge

## 1. Semua satuan PMO (`oms_product_unit`)

| # | PMO id | title | published |
|---|--------|-------|-----------|
| 1 | `1717092026163307` | **gelasin** | active |
| 2 | `5013082026141116` | **Lusinan** | active |
| 3 | `5017092026160218` | **Satuann** | active |
| 4 | `718092026120640` | **SATUANBARU** | active |
| 5 | `817092026163245` | **dummy** | active |
| 6 | `9417092026160619` | **satuann** | active |
| 7 | `9506012026014611` | **Dus** | active |
| 8 | `9506012026014612` | **Pcs** | active |
| 9 | `9506012026014613` | **Pack** | active |
| 10 | `9506012026014614` | **Pail** | active |
| 11 | `9506012026014615` | **Drum** | active |
| 12 | `9506012026014616` | **Jerigen** | active |

**Total PMO:** 12

## 2. Semua satuan IPM DEV (`units`)

| # | unit_id | unit_name | unit_short_name | status | ref_unit_id |
|---|---------|-----------|-----------------|--------|-------------|
| 1 | 1 | **Kilogram** | kg | aktif | — |
| 2 | 2 | **Jerigen** | Jerigen | aktif | 9506012026014616 |
| 3 | 3 | **Liter** | LTR | aktif | — |
| 4 | 5 | **Drum** | Drum | aktif | 9506012026014615 |
| 5 | 7 | **DOS** | DOS | aktif | — |
| 6 | 8 | **Pack** | Pack | aktif | 9506012026014613 |
| 7 | 9 | **Piece** | pcs | aktif | — |
| 8 | 21 | **Pail** | Pail | aktif | 9506012026014614 |
| 9 | 22 | **Gram** | Gr | aktif | — |
| 10 | 23 | **testing323** | TST221 | nonaktif | — |
| 11 | 24 | **testing2213** | TST245 | nonaktif | — |
| 12 | 25 | **Sak** | Sak | aktif | — |
| 13 | 26 | **RIM** | RIM | aktif | — |
| 14 | 27 | **GRAM** | GR | nonaktif | — |
| 15 | 28 | **probe** | probe | nonaktif | 999999 |
| 16 | 29 | **IPMTEST Unit 6aa8422302f5b** | IPMTEST Unit 6aa8422302f5b | nonaktif | 4915092026015115 |
| 17 | 30 | **IPMTEST Unit 6aa8422387c0a MODIFIED** | IPMTEST Unit 6aa8422387c0a MODIFIED | nonaktif | 7215092026015115 |
| 18 | 31 | **IPMTEST Unit 6aa84223f3d3f** | IPMTEST Unit 6aa84223f3d3f | nonaktif | 9415092026015116 |
| 19 | 32 | **IPMTEST Unit 6aa84224720a7** | IPMTEST Unit 6aa84224720a7 | nonaktif | 6915092026015116 |
| 20 | 33 | **IPMTEST Unit 6aa8423fcae36** | IPMTEST Unit 6aa8423fcae36 | nonaktif | 3415092026015143 |
| 21 | 34 | **IPMTEST Unit 6aa8424030198 MODIFIED** | IPMTEST Unit 6aa8424030198 MODIFIED | nonaktif | 3315092026015144 |
| 22 | 35 | **IPMTEST Unit 6aa84240c4862** | IPMTEST Unit 6aa84240c4862 | nonaktif | 715092026015144 |
| 23 | 36 | **IPMTEST Unit 6aa842413b53d** | IPMTEST Unit 6aa842413b53d | nonaktif | 815092026015145 |
| 24 | 37 | **IPMTEST Unit 6aa843049d62c** | IPMTEST Unit 6aa843049d62c | nonaktif | 4715092026015500 |
| 25 | 38 | **IPMTEST Unit 6aa843051b4fd** | IPMTEST Unit 6aa843051b4fd | nonaktif | 9315092026015501 |
| 26 | 39 | **IPMTEST Unit 6aa8430ca4566** | IPMTEST Unit 6aa8430ca4566 | nonaktif | 715092026015508 |
| 27 | 40 | **IPMTEST Unit 6aa8430d1b6c6 MODIFIED** | IPMTEST Unit 6aa8430d1b6c6 MODIFIED | nonaktif | 9515092026015509 |
| 28 | 41 | **IPMTEST Unit 6aa8430db52ef** | IPMTEST Unit 6aa8430db52ef | nonaktif | 4015092026015509 |
| 29 | 42 | **IPMTEST Unit 6aa8430e26b6a** | IPMTEST Unit 6aa8430e26b6a | nonaktif | 8315092026015510 |
| 30 | 43 | **IPMTEST Unit 6aa843110f8d6** | IPMTEST Unit 6aa843110f8d6 | nonaktif | 4815092026015513 |
| 31 | 44 | **IPMTEST Unit 6aa8431167505** | IPMTEST Unit 6aa8431167505 | nonaktif | 7315092026015513 |
| 32 | 45 | **IPMTEST Unit 6aa843284ac35** | IPMTEST Unit 6aa843284ac35 | nonaktif | 4415092026015536 |
| 33 | 46 | **IPMTEST Unit 6aa8432d92e95 MODIFIED** | IPMTEST Unit 6aa8432d92e95 MODIFIED | nonaktif | 5215092026015541 |
| 34 | 47 | **IPMTEST Unit 6aa8432e1370a** | IPMTEST Unit 6aa8432e1370a | nonaktif | 9115092026015542 |
| 35 | 48 | **IPMTEST Unit 6aa8432e7f3f1** | IPMTEST Unit 6aa8432e7f3f1 | nonaktif | 6715092026015542 |
| 36 | 49 | **IPMTEST Unit 6aa84330bc062** | IPMTEST Unit 6aa84330bc062 | nonaktif | 9215092026015544 |
| 37 | 50 | **IPMTEST Unit 6aa843310f796** | IPMTEST Unit 6aa843310f796 | nonaktif | 4515092026015545 |
| 38 | 51 | **IPMTEST Unit 6aa84806d7505** | IPMTEST Unit 6aa84806d7505 | nonaktif | 9115092026021622 |
| 39 | 52 | **IPMTEST Unit 6aa84808a639b MODIFIED** | IPMTEST Unit 6aa84808a639b MODIFIED | nonaktif | 4115092026021624 |
| 40 | 53 | **IPMTEST Unit 6aa848092b078** | IPMTEST Unit 6aa848092b078 | nonaktif | 8315092026021625 |
| 41 | 54 | **IPMTEST Unit 6aa848098e45c** | IPMTEST Unit 6aa848098e45c | nonaktif | 1315092026021625 |
| 42 | 55 | **IPMTEST Unit 6aa8480bc57ea** | IPMTEST Unit 6aa8480bc57ea | nonaktif | 4515092026021627 |
| 43 | 56 | **IPMTEST Unit 6aa8480c3664b** | IPMTEST Unit 6aa8480c3664b | nonaktif | 9415092026021628 |
| 44 | 57 | **IPMTEST Unit 6aa848343aa02** | IPMTEST Unit 6aa848343aa02 | nonaktif | 2315092026021708 |
| 45 | 58 | **IPMTEST Unit 6aa8483490a50 MODIFIED** | IPMTEST Unit 6aa8483490a50 MODIFIED | nonaktif | 1315092026021708 |
| 46 | 59 | **IPMTEST Unit 6aa848350d0c7** | IPMTEST Unit 6aa848350d0c7 | nonaktif | 5915092026021709 |
| 47 | 60 | **IPMTEST Unit 6aa8483576262** | IPMTEST Unit 6aa8483576262 | nonaktif | 6015092026021709 |
| 48 | 61 | **IPMTEST Unit 6aa848378c6f2** | IPMTEST Unit 6aa848378c6f2 | nonaktif | 8215092026021711 |
| 49 | 62 | **IPMTEST Unit 6aa848380242e** | IPMTEST Unit 6aa848380242e | nonaktif | 4615092026021712 |
| 50 | 63 | **IPMTEST Unit 6aa8486e6517f** | IPMTEST Unit 6aa8486e6517f | nonaktif | 3515092026021806 |
| 51 | 64 | **IPMTEST Unit 6aa8486fc504a MODIFIED** | IPMTEST Unit 6aa8486fc504a MODIFIED | nonaktif | 3915092026021807 |
| 52 | 65 | **IPMTEST Unit 6aa84870c62d5** | IPMTEST Unit 6aa84870c62d5 | nonaktif | 3715092026021808 |
| 53 | 66 | **IPMTEST Unit 6aa84871d1339** | IPMTEST Unit 6aa84871d1339 | nonaktif | 1115092026021809 |
| 54 | 67 | **IPMTEST Unit 6aa848768eea6** | IPMTEST Unit 6aa848768eea6 | nonaktif | 4315092026021814 |
| 55 | 68 | **IPMTEST Unit 6aa84878561d5** | IPMTEST Unit 6aa84878561d5 | nonaktif | 8215092026021816 |
| 56 | 69 | **IPMTEST Unit 6aa94e2513b66** | IPMTEST Unit 6aa94e2513b66 | nonaktif | 9015092026205445 |
| 57 | 70 | **IPMTEST Unit 6aa94e2621ad9 MODIFIED** | IPMTEST Unit 6aa94e2621ad9 MODIFIED | nonaktif | 2115092026205446 |
| 58 | 71 | **IPMTEST Unit 6aa94e27bd8a2** | IPMTEST Unit 6aa94e27bd8a2 | nonaktif | 7515092026205447 |
| 59 | 72 | **IPMTEST Unit 6aa94e2961115** | IPMTEST Unit 6aa94e2961115 | nonaktif | 3015092026205449 |
| 60 | 73 | **IPMTEST Unit 6aa94e3382a47** | IPMTEST Unit 6aa94e3382a47 | nonaktif | 3015092026205459 |
| 61 | 74 | **IPMTEST Unit 6aa94e36ced5e** | IPMTEST Unit 6aa94e36ced5e | nonaktif | 9015092026205502 |
| 62 | 75 | **IPMTEST Unit 6aa94e403614e** | IPMTEST Unit 6aa94e403614e | nonaktif | 9215092026205512 |
| 63 | 76 | **IPMTEST Unit 6aa94e41543ed MODIFIED** | IPMTEST Unit 6aa94e41543ed MODIFIED | nonaktif | 6515092026205513 |
| 64 | 77 | **IPMTEST Unit 6aa94e432c1ba** | IPMTEST Unit 6aa94e432c1ba | nonaktif | 3715092026205515 |
| 65 | 78 | **IPMTEST Unit 6aa94e44b8537** | IPMTEST Unit 6aa94e44b8537 | nonaktif | 4115092026205516 |
| 66 | 79 | **IPMTEST Unit 6aa94e4fb05ab** | IPMTEST Unit 6aa94e4fb05ab | nonaktif | 6215092026205527 |
| 67 | 80 | **IPMTEST Unit 6aa94e51eafe1** | IPMTEST Unit 6aa94e51eafe1 | nonaktif | 1815092026205529 |
| 68 | 81 | **IPMTEST Unit 6aa94f0f91ea1** | IPMTEST Unit 6aa94f0f91ea1 | nonaktif | 6615092026205839 |
| 69 | 82 | **IPMTEST Unit 6aa94f110a81e MODIFIED** | IPMTEST Unit 6aa94f110a81e MODIFIED | nonaktif | 6115092026205841 |
| 70 | 83 | **IPMTEST Unit 6aa94f128429e** | IPMTEST Unit 6aa94f128429e | nonaktif | 6115092026205842 |
| 71 | 84 | **IPMTEST Unit 6aa94f14a60e9** | IPMTEST Unit 6aa94f14a60e9 | nonaktif | 915092026205844 |
| 72 | 85 | **IPMTEST Unit 6aa94f1e62c59** | IPMTEST Unit 6aa94f1e62c59 | nonaktif | 4515092026205854 |
| 73 | 86 | **IPMTEST Unit 6aa94f203a705** | IPMTEST Unit 6aa94f203a705 | nonaktif | 415092026205856 |
| 74 | 87 | **IPMTEST Unit 6aa9619f0d4ae** | IPMTEST Unit 6aa9619f0d4ae | nonaktif | 6615092026221751 |
| 75 | 88 | **IPMTEST Unit 6aa961a08559d MODIFIED** | IPMTEST Unit 6aa961a08559d MODIFIED | nonaktif | 7215092026221752 |
| 76 | 89 | **IPMTEST Unit 6aa961a2548d0** | IPMTEST Unit 6aa961a2548d0 | nonaktif | 515092026221754 |
| 77 | 90 | **IPMTEST Unit 6aa961a3b5b24** | IPMTEST Unit 6aa961a3b5b24 | nonaktif | 4815092026221755 |
| 78 | 91 | **IPMTEST Unit 6aa961aba2f0e** | IPMTEST Unit 6aa961aba2f0e | nonaktif | 2215092026221803 |
| 79 | 92 | **IPMTEST Unit 6aa961adde2d3** | IPMTEST Unit 6aa961adde2d3 | nonaktif | 8715092026221805 |
| 80 | 93 | **TEST EDITED** | TEST EDITED | nonaktif | 715092026222937 |
| 81 | 94 | **IPMTEST Unit 6aa9a1aa1c882** | IPMTEST Unit 6aa9a1aa1c882 | nonaktif | 6916092026025106 |
| 82 | 95 | **IPMTEST Unit 6aa9a1ac05bce** | IPMTEST Unit 6aa9a1ac05bce | nonaktif | 1216092026025108 |
| 83 | 96 | **IPMTEST Unit 6aa9aaba9729b** | IPMTEST Unit 6aa9aaba9729b | aktif | 1516092026032946 |
| 84 | 97 | **IPMTEST Unit 6aa9aabaf04d6** | IPMTEST Unit 6aa9aabaf04d6 | nonaktif | 516092026032946 |
| 85 | 98 | **IPMTEST Unit 6aa9ab11f34c3** | IPMTEST Unit 6aa9ab11f34c3 | aktif | 7716092026033114 |
| 86 | 99 | **IPMTEST Unit 6aa9ab12e6c4d** | IPMTEST Unit 6aa9ab12e6c4d | nonaktif | 9816092026033114 |
| 87 | 100 | **IPMTEST Unit 6aa9ab6d41031** | IPMTEST Unit 6aa9ab6d41031 | aktif | 8916092026033245 |
| 88 | 101 | **IPMTEST Unit 6aa9ab6fb5286** | IPMTEST Unit 6aa9ab6fb5286 | nonaktif | 6316092026033247 |
| 89 | 102 | **IPMTEST Unit 6aa9ab78c6e7f** | IPMTEST Unit 6aa9ab78c6e7f | aktif | 316092026033256 |
| 90 | 103 | **IPMTEST Unit 6aa9ab790bc19** | IPMTEST Unit 6aa9ab790bc19 | nonaktif | 3216092026033257 |
| 91 | 104 | **IPMTEST Unit 6aa9ab7a42c77** | IPMTEST Unit 6aa9ab7a42c77 | nonaktif | 5616092026033258 |
| 92 | 105 | **IPMTEST Unit 6aa9ab7a9a3ae MODIFIED** | IPMTEST Unit 6aa9ab7a9a3ae MODIFIED | nonaktif | 8616092026033258 |
| 93 | 106 | **IPMTEST Unit 6aa9ab7b37953** | IPMTEST Unit 6aa9ab7b37953 | nonaktif | 4116092026033259 |
| 94 | 107 | **IPMTEST Unit 6aa9ab7bbc975** | IPMTEST Unit 6aa9ab7bbc975 | nonaktif | 7616092026033259 |
| 95 | 108 | **IPMTEST Unit 6aa9ab7e52836** | IPMTEST Unit 6aa9ab7e52836 | nonaktif | 10016092026033302 |
| 96 | 109 | **IPMTEST Unit 6aa9ab7f14dfc** | IPMTEST Unit 6aa9ab7f14dfc | nonaktif | 5916092026033303 |
| 97 | 110 | **IPMTEST Unit 6aa9abfb2e77a** | IPMTEST Unit 6aa9abfb2e77a | aktif | 4816092026033507 |
| 98 | 111 | **IPMTEST Unit 6aa9abfc60179** | IPMTEST Unit 6aa9abfc60179 | nonaktif | 2316092026033508 |
| 99 | 112 | **IPMTEST Unit 6aa9abfd967ac** | IPMTEST Unit 6aa9abfd967ac | nonaktif | 5816092026033509 |
| 100 | 113 | **IPMTEST Unit 6aa9abfe099b6 MODIFIED** | IPMTEST Unit 6aa9abfe099b6 MODIFIED | nonaktif | 4616092026033510 |
| 101 | 114 | **IPMTEST Unit 6aa9abfe9ecb5** | IPMTEST Unit 6aa9abfe9ecb5 | nonaktif | 3716092026033510 |
| 102 | 115 | **IPMTEST Unit 6aa9abff25d52** | IPMTEST Unit 6aa9abff25d52 | nonaktif | 2216092026033511 |
| 103 | 116 | **IPMTEST Unit 6aa9ac01af394** | IPMTEST Unit 6aa9ac01af394 | nonaktif | 8716092026033513 |
| 104 | 117 | **IPMTEST Unit 6aa9ac0273748** | IPMTEST Unit 6aa9ac0273748 | nonaktif | 9516092026033514 |
| 105 | 118 | **satuann** | satuann | aktif | 9417092026160619 |
| 106 | 119 | **dummy** | dummy | nonaktif | 3317092026163223 |
| 107 | 120 | **dummy** | dummy | aktif | 817092026163245 |
| 108 | 121 | **gelasin** | gelasin | aktif | 1717092026163307 |
| 109 | 122 | **SATUANBARU** | SATUANBARU | aktif | 718092026120640 |
| 110 | 123 | **BARU** | BARU | nonaktif | 4018092026120936 |
| 111 | 124 | **BARU** | BARU | nonaktif | 2918092026121526 |
| 112 | 125 | **BARU** | BARU | nonaktif | 8018092026122352 |
| 113 | 126 | **Dus** | Dus | aktif | 9506012026014611 |
| 114 | 127 | **Pcs** | Pcs | aktif | 9506012026014612 |
| 115 | 128 | **Lusinan** | Lusinan | aktif | 5013082026141116 |
| 116 | 129 | **Satuann** | Satuann | aktif | 5017092026160218 |

**Total DEV:** 116

## 3. String `satuan` yang dipakai di produk PMO (`oms_product.satuan`)

| satuan (teks) | jumlah produk |
|---------------|---------------|
| **drum** | 16 |
| **dus** | 201 |
| **jerigen** | 19 |
| **pack** | 17 |
| **pail** | 21 |
| **pcs** | 23 |

## 4. Calon merge (normalisasi nama, PMO → DEV)

| PMO title | Match DEV? | unit_id DEV | catatan |
|-----------|------------|-------------|---------|
| **gelasin** | gelasin (gelasin) | 121 | ✅ cocok |
| **Lusinan** | Lusinan (Lusinan) | 128 | ✅ cocok |
| **Satuann** | satuann (satuann) / Satuann (Satuann) | 118, 129 | ⚠️ beberapa kandidat — pilih 1 |
| **SATUANBARU** | SATUANBARU (SATUANBARU) | 122 | ✅ cocok |
| **dummy** | dummy (dummy) / dummy (dummy) | 119, 120 | ⚠️ beberapa kandidat — pilih 1 |
| **satuann** | satuann (satuann) / Satuann (Satuann) | 118, 129 | ⚠️ beberapa kandidat — pilih 1 |
| **Dus** | Dus (Dus) | 126 | ✅ cocok — **cek DOS vs Dus** |
| **Pcs** | Piece (pcs) / Pcs (Pcs) | 9, 127 | ⚠️ beberapa kandidat — pilih 1 |
| **Pack** | Pack (Pack) | 8 | ✅ cocok |
| **Pail** | Pail (Pail) | 21 | ✅ cocok |
| **Drum** | Drum (Drum) | 5 | ✅ cocok |
| **Jerigen** | Jerigen (Jerigen) | 2 | ✅ cocok |

## 5. Satuan di DEV yang tidak ada di PMO (mungkin lokal / legacy)

| unit_id | unit_name | short | status |
|---------|-----------|-------|--------|
| 1 | **Kilogram** | kg | aktif |
| 3 | **Liter** | LTR | aktif |
| 22 | **Gram** | Gr | aktif |
| 23 | **testing323** | TST221 | nonaktif |
| 24 | **testing2213** | TST245 | nonaktif |
| 25 | **Sak** | Sak | aktif |
| 26 | **RIM** | RIM | aktif |
| 27 | **GRAM** | GR | nonaktif |
| 28 | **probe** | probe | nonaktif |
| 29 | **IPMTEST Unit 6aa8422302f5b** | IPMTEST Unit 6aa8422302f5b | nonaktif |
| 30 | **IPMTEST Unit 6aa8422387c0a MODIFIED** | IPMTEST Unit 6aa8422387c0a MODIFIED | nonaktif |
| 31 | **IPMTEST Unit 6aa84223f3d3f** | IPMTEST Unit 6aa84223f3d3f | nonaktif |
| 32 | **IPMTEST Unit 6aa84224720a7** | IPMTEST Unit 6aa84224720a7 | nonaktif |
| 33 | **IPMTEST Unit 6aa8423fcae36** | IPMTEST Unit 6aa8423fcae36 | nonaktif |
| 34 | **IPMTEST Unit 6aa8424030198 MODIFIED** | IPMTEST Unit 6aa8424030198 MODIFIED | nonaktif |
| 35 | **IPMTEST Unit 6aa84240c4862** | IPMTEST Unit 6aa84240c4862 | nonaktif |
| 36 | **IPMTEST Unit 6aa842413b53d** | IPMTEST Unit 6aa842413b53d | nonaktif |
| 37 | **IPMTEST Unit 6aa843049d62c** | IPMTEST Unit 6aa843049d62c | nonaktif |
| 38 | **IPMTEST Unit 6aa843051b4fd** | IPMTEST Unit 6aa843051b4fd | nonaktif |
| 39 | **IPMTEST Unit 6aa8430ca4566** | IPMTEST Unit 6aa8430ca4566 | nonaktif |
| 40 | **IPMTEST Unit 6aa8430d1b6c6 MODIFIED** | IPMTEST Unit 6aa8430d1b6c6 MODIFIED | nonaktif |
| 41 | **IPMTEST Unit 6aa8430db52ef** | IPMTEST Unit 6aa8430db52ef | nonaktif |
| 42 | **IPMTEST Unit 6aa8430e26b6a** | IPMTEST Unit 6aa8430e26b6a | nonaktif |
| 43 | **IPMTEST Unit 6aa843110f8d6** | IPMTEST Unit 6aa843110f8d6 | nonaktif |
| 44 | **IPMTEST Unit 6aa8431167505** | IPMTEST Unit 6aa8431167505 | nonaktif |
| 45 | **IPMTEST Unit 6aa843284ac35** | IPMTEST Unit 6aa843284ac35 | nonaktif |
| 46 | **IPMTEST Unit 6aa8432d92e95 MODIFIED** | IPMTEST Unit 6aa8432d92e95 MODIFIED | nonaktif |
| 47 | **IPMTEST Unit 6aa8432e1370a** | IPMTEST Unit 6aa8432e1370a | nonaktif |
| 48 | **IPMTEST Unit 6aa8432e7f3f1** | IPMTEST Unit 6aa8432e7f3f1 | nonaktif |
| 49 | **IPMTEST Unit 6aa84330bc062** | IPMTEST Unit 6aa84330bc062 | nonaktif |
| 50 | **IPMTEST Unit 6aa843310f796** | IPMTEST Unit 6aa843310f796 | nonaktif |
| 51 | **IPMTEST Unit 6aa84806d7505** | IPMTEST Unit 6aa84806d7505 | nonaktif |
| 52 | **IPMTEST Unit 6aa84808a639b MODIFIED** | IPMTEST Unit 6aa84808a639b MODIFIED | nonaktif |
| 53 | **IPMTEST Unit 6aa848092b078** | IPMTEST Unit 6aa848092b078 | nonaktif |
| 54 | **IPMTEST Unit 6aa848098e45c** | IPMTEST Unit 6aa848098e45c | nonaktif |
| 55 | **IPMTEST Unit 6aa8480bc57ea** | IPMTEST Unit 6aa8480bc57ea | nonaktif |
| 56 | **IPMTEST Unit 6aa8480c3664b** | IPMTEST Unit 6aa8480c3664b | nonaktif |
| 57 | **IPMTEST Unit 6aa848343aa02** | IPMTEST Unit 6aa848343aa02 | nonaktif |
| 58 | **IPMTEST Unit 6aa8483490a50 MODIFIED** | IPMTEST Unit 6aa8483490a50 MODIFIED | nonaktif |
| 59 | **IPMTEST Unit 6aa848350d0c7** | IPMTEST Unit 6aa848350d0c7 | nonaktif |
| 60 | **IPMTEST Unit 6aa8483576262** | IPMTEST Unit 6aa8483576262 | nonaktif |
| 61 | **IPMTEST Unit 6aa848378c6f2** | IPMTEST Unit 6aa848378c6f2 | nonaktif |
| 62 | **IPMTEST Unit 6aa848380242e** | IPMTEST Unit 6aa848380242e | nonaktif |
| 63 | **IPMTEST Unit 6aa8486e6517f** | IPMTEST Unit 6aa8486e6517f | nonaktif |
| 64 | **IPMTEST Unit 6aa8486fc504a MODIFIED** | IPMTEST Unit 6aa8486fc504a MODIFIED | nonaktif |
| 65 | **IPMTEST Unit 6aa84870c62d5** | IPMTEST Unit 6aa84870c62d5 | nonaktif |
| 66 | **IPMTEST Unit 6aa84871d1339** | IPMTEST Unit 6aa84871d1339 | nonaktif |
| 67 | **IPMTEST Unit 6aa848768eea6** | IPMTEST Unit 6aa848768eea6 | nonaktif |
| 68 | **IPMTEST Unit 6aa84878561d5** | IPMTEST Unit 6aa84878561d5 | nonaktif |
| 69 | **IPMTEST Unit 6aa94e2513b66** | IPMTEST Unit 6aa94e2513b66 | nonaktif |
| 70 | **IPMTEST Unit 6aa94e2621ad9 MODIFIED** | IPMTEST Unit 6aa94e2621ad9 MODIFIED | nonaktif |
| 71 | **IPMTEST Unit 6aa94e27bd8a2** | IPMTEST Unit 6aa94e27bd8a2 | nonaktif |
| 72 | **IPMTEST Unit 6aa94e2961115** | IPMTEST Unit 6aa94e2961115 | nonaktif |
| 73 | **IPMTEST Unit 6aa94e3382a47** | IPMTEST Unit 6aa94e3382a47 | nonaktif |
| 74 | **IPMTEST Unit 6aa94e36ced5e** | IPMTEST Unit 6aa94e36ced5e | nonaktif |
| 75 | **IPMTEST Unit 6aa94e403614e** | IPMTEST Unit 6aa94e403614e | nonaktif |
| 76 | **IPMTEST Unit 6aa94e41543ed MODIFIED** | IPMTEST Unit 6aa94e41543ed MODIFIED | nonaktif |
| 77 | **IPMTEST Unit 6aa94e432c1ba** | IPMTEST Unit 6aa94e432c1ba | nonaktif |
| 78 | **IPMTEST Unit 6aa94e44b8537** | IPMTEST Unit 6aa94e44b8537 | nonaktif |
| 79 | **IPMTEST Unit 6aa94e4fb05ab** | IPMTEST Unit 6aa94e4fb05ab | nonaktif |
| 80 | **IPMTEST Unit 6aa94e51eafe1** | IPMTEST Unit 6aa94e51eafe1 | nonaktif |
| 81 | **IPMTEST Unit 6aa94f0f91ea1** | IPMTEST Unit 6aa94f0f91ea1 | nonaktif |
| 82 | **IPMTEST Unit 6aa94f110a81e MODIFIED** | IPMTEST Unit 6aa94f110a81e MODIFIED | nonaktif |
| 83 | **IPMTEST Unit 6aa94f128429e** | IPMTEST Unit 6aa94f128429e | nonaktif |
| 84 | **IPMTEST Unit 6aa94f14a60e9** | IPMTEST Unit 6aa94f14a60e9 | nonaktif |
| 85 | **IPMTEST Unit 6aa94f1e62c59** | IPMTEST Unit 6aa94f1e62c59 | nonaktif |
| 86 | **IPMTEST Unit 6aa94f203a705** | IPMTEST Unit 6aa94f203a705 | nonaktif |
| 87 | **IPMTEST Unit 6aa9619f0d4ae** | IPMTEST Unit 6aa9619f0d4ae | nonaktif |
| 88 | **IPMTEST Unit 6aa961a08559d MODIFIED** | IPMTEST Unit 6aa961a08559d MODIFIED | nonaktif |
| 89 | **IPMTEST Unit 6aa961a2548d0** | IPMTEST Unit 6aa961a2548d0 | nonaktif |
| 90 | **IPMTEST Unit 6aa961a3b5b24** | IPMTEST Unit 6aa961a3b5b24 | nonaktif |
| 91 | **IPMTEST Unit 6aa961aba2f0e** | IPMTEST Unit 6aa961aba2f0e | nonaktif |
| 92 | **IPMTEST Unit 6aa961adde2d3** | IPMTEST Unit 6aa961adde2d3 | nonaktif |
| 93 | **TEST EDITED** | TEST EDITED | nonaktif |
| 94 | **IPMTEST Unit 6aa9a1aa1c882** | IPMTEST Unit 6aa9a1aa1c882 | nonaktif |
| 95 | **IPMTEST Unit 6aa9a1ac05bce** | IPMTEST Unit 6aa9a1ac05bce | nonaktif |
| 96 | **IPMTEST Unit 6aa9aaba9729b** | IPMTEST Unit 6aa9aaba9729b | aktif |
| 97 | **IPMTEST Unit 6aa9aabaf04d6** | IPMTEST Unit 6aa9aabaf04d6 | nonaktif |
| 98 | **IPMTEST Unit 6aa9ab11f34c3** | IPMTEST Unit 6aa9ab11f34c3 | aktif |
| 99 | **IPMTEST Unit 6aa9ab12e6c4d** | IPMTEST Unit 6aa9ab12e6c4d | nonaktif |
| 100 | **IPMTEST Unit 6aa9ab6d41031** | IPMTEST Unit 6aa9ab6d41031 | aktif |
| 101 | **IPMTEST Unit 6aa9ab6fb5286** | IPMTEST Unit 6aa9ab6fb5286 | nonaktif |
| 102 | **IPMTEST Unit 6aa9ab78c6e7f** | IPMTEST Unit 6aa9ab78c6e7f | aktif |
| 103 | **IPMTEST Unit 6aa9ab790bc19** | IPMTEST Unit 6aa9ab790bc19 | nonaktif |
| 104 | **IPMTEST Unit 6aa9ab7a42c77** | IPMTEST Unit 6aa9ab7a42c77 | nonaktif |
| 105 | **IPMTEST Unit 6aa9ab7a9a3ae MODIFIED** | IPMTEST Unit 6aa9ab7a9a3ae MODIFIED | nonaktif |
| 106 | **IPMTEST Unit 6aa9ab7b37953** | IPMTEST Unit 6aa9ab7b37953 | nonaktif |
| 107 | **IPMTEST Unit 6aa9ab7bbc975** | IPMTEST Unit 6aa9ab7bbc975 | nonaktif |
| 108 | **IPMTEST Unit 6aa9ab7e52836** | IPMTEST Unit 6aa9ab7e52836 | nonaktif |
| 109 | **IPMTEST Unit 6aa9ab7f14dfc** | IPMTEST Unit 6aa9ab7f14dfc | nonaktif |
| 110 | **IPMTEST Unit 6aa9abfb2e77a** | IPMTEST Unit 6aa9abfb2e77a | aktif |
| 111 | **IPMTEST Unit 6aa9abfc60179** | IPMTEST Unit 6aa9abfc60179 | nonaktif |
| 112 | **IPMTEST Unit 6aa9abfd967ac** | IPMTEST Unit 6aa9abfd967ac | nonaktif |
| 113 | **IPMTEST Unit 6aa9abfe099b6 MODIFIED** | IPMTEST Unit 6aa9abfe099b6 MODIFIED | nonaktif |
| 114 | **IPMTEST Unit 6aa9abfe9ecb5** | IPMTEST Unit 6aa9abfe9ecb5 | nonaktif |
| 115 | **IPMTEST Unit 6aa9abff25d52** | IPMTEST Unit 6aa9abff25d52 | nonaktif |
| 116 | **IPMTEST Unit 6aa9ac01af394** | IPMTEST Unit 6aa9ac01af394 | nonaktif |
| 117 | **IPMTEST Unit 6aa9ac0273748** | IPMTEST Unit 6aa9ac0273748 | nonaktif |
| 123 | **BARU** | BARU | nonaktif |
| 124 | **BARU** | BARU | nonaktif |
| 125 | **BARU** | BARU | nonaktif |

## 6. Highlight DOS / Dus / Dos

### PMO
- `9506012026014611` → **Dus** (active)

### DEV
- unit_id **7** → name=`DOS` short=`DOS` status=1
- unit_id **126** → name=`Dus` short=`Dus` status=1

> Saran merge: pakai ejaan **PMO** (`Dus`) sebagai kanonik; alias IPM `DOS`/`Dos` diarahkan ke unit yang sama (jangan biarkan 2 unit_id hidup untuk arti yang sama).
