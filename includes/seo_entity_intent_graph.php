<?php
declare(strict_types=1);

/**
 * Varlık / niyet grafiği (SQL yok). Üretim head bu dosyayı seo_runtime yüklenirken ister.
 * Tam grafik verisi ileride genişletilebilir; şu an güvenli minimal uygulama + İzmir ilçe SSOT.
 */

/**
 * URL slug → dahili graf anahtarı (şimdilik bire bir).
 */
function seo_ei_graph_key_for_url_slug(string $anchor): string
{
    return $anchor;
}

/**
 * @return array<string, mixed>|null
 */
function seo_ei_service_spec_for_pillar_slug(string $anchor): ?array
{
    if ($anchor === '') {
        return null;
    }

    static $specs = null;
    if ($specs === null) {
        $specs = [
            'izmir-evden-eve-nakliyat' => [
                'entity_id' => 'izmir-evden-eve-nakliyat',
                'primary_intent' => 'hire_local_mover',
                'secondary_intents' => ['get_moving_quote', 'compare_moving_companies', 'schedule_home_survey'],
                'informational_intents' => ['moving_cost_izmir', 'moving_checklist', 'safe_furniture_packing', 'best_moving_company_izmir'],
            ],
            'sehirler-arasi-nakliyat' => [
                'entity_id' => 'sehirler-arasi-nakliyat',
                'primary_intent' => 'hire_intercity_mover',
                'secondary_intents' => ['get_intercity_quote', 'route_planning'],
                'informational_intents' => ['intercity_moving_duration', 'intercity_insurance_coverage'],
            ],
            'asansorlu-nakliyat' => [
                'entity_id' => 'asansorlu-nakliyat',
                'primary_intent' => 'hire_elevator_moving',
                'secondary_intents' => ['get_moving_quote', 'check_building_eligibility'],
                'informational_intents' => ['elevator_moving_cost', 'high_floor_moving_tips'],
            ],
            'sehirici-nakliyat' => [
                'entity_id' => 'sehirici-nakliyat',
                'primary_intent' => 'hire_local_mover',
                'secondary_intents' => ['get_moving_quote', 'same_day_moving'],
                'informational_intents' => ['local_moving_cost', 'moving_within_izmir'],
            ],
            'parca-esya-tasima' => [
                'entity_id' => 'parca-esya-tasima',
                'primary_intent' => 'hire_partial_mover',
                'secondary_intents' => ['get_moving_quote', 'single_item_transport'],
                'informational_intents' => ['partial_moving_cost', 'appliance_transport_tips'],
            ],
            'izmir-esya-depolama' => [
                'entity_id' => 'izmir-esya-depolama',
                'primary_intent' => 'rent_storage',
                'secondary_intents' => ['get_storage_quote', 'short_term_storage'],
                'informational_intents' => ['storage_cost_izmir', 'safe_storage_tips'],
            ],
            'izmir-ofis-tasimaciligi' => [
                'entity_id' => 'izmir-ofis-tasimaciligi',
                'primary_intent' => 'hire_office_mover',
                'secondary_intents' => ['get_corporate_quote', 'weekend_office_move'],
                'informational_intents' => ['office_moving_checklist', 'it_equipment_packing'],
            ],
            'izmir-ceyiz-tasima-nakliyat' => [
                'entity_id' => 'izmir-ceyiz-tasima-nakliyat',
                'primary_intent' => 'hire_trousseau_mover',
                'secondary_intents' => ['get_moving_quote', 'fragile_item_packing'],
                'informational_intents' => ['trousseau_moving_tips', 'wedding_moving_timeline'],
            ],
            'antika-ve-piyano-tasima' => [
                'entity_id' => 'antika-ve-piyano-tasima',
                'primary_intent' => 'hire_specialty_mover',
                'secondary_intents' => ['get_specialty_quote', 'piano_transport'],
                'informational_intents' => ['antique_packing_methods', 'piano_moving_cost'],
            ],
            'hakkimizda' => [
                'entity_id' => 'hakkimizda',
                'primary_intent' => 'verify_company_profile',
                'secondary_intents' => ['check_company_history', 'check_quality_documents'],
                'informational_intents' => ['company_experience', 'quality_certificates'],
            ],
            'ekibimiz' => [
                'entity_id' => 'ekibimiz',
                'primary_intent' => 'verify_team_trust',
                'secondary_intents' => ['check_staff_experience', 'review_service_team'],
                'informational_intents' => ['team_structure', 'operational_capacity'],
            ],
            'belgelerimiz' => [
                'entity_id' => 'belgelerimiz',
                'primary_intent' => 'verify_documents',
                'secondary_intents' => ['check_certifications', 'check_awards'],
                'informational_intents' => ['iso_documents', 'service_compliance'],
            ],
            'teklif-alin' => [
                'entity_id' => 'teklif-alin',
                'primary_intent' => 'request_quote',
                'secondary_intents' => ['book_home_survey', 'share_move_details'],
                'informational_intents' => ['quote_process', 'price_factors'],
            ],
            'iletisim' => [
                'entity_id' => 'iletisim',
                'primary_intent' => 'contact_company',
                'secondary_intents' => ['call_now', 'send_contact_form'],
                'informational_intents' => ['office_location', 'working_hours'],
            ],
            'basinda-biz' => [
                'entity_id' => 'basinda-biz',
                'primary_intent' => 'verify_brand_reputation',
                'secondary_intents' => ['review_press_mentions', 'review_media_coverage'],
                'informational_intents' => ['brand_mentions', 'public_references'],
            ],
            'blog' => [
                'entity_id' => 'blog',
                'primary_intent' => 'learn_moving_tips',
                'secondary_intents' => ['read_guides', 'compare_moving_methods'],
                'informational_intents' => ['packing_guides', 'moving_planning'],
            ],
        ];
    }

    return $specs[$anchor] ?? [
        'entity_id' => $anchor,
        'primary_intent' => '',
        'secondary_intents' => [],
        'informational_intents' => [],
    ];
}

/**
 * @return list<string> "İlçe, İzmir, Türkiye" biçimi (areaServed SSOT)
 */
function seo_ei_izmir_metro_district_location_defs(): array
{
    $out = [];
    foreach (seo_ei_izmir_district_names_local_pack_order() as $name) {
        $out[] = $name . ', İzmir, Türkiye';
    }

    return $out;
}

/**
 * @return list<string>
 */
function seo_ei_izmir_district_names_local_pack_order(): array
{
    return [
        // Merkez / yüksek yoğunluklu metropol ilçeleri (yerel paket önceliği)
        'Konak', 'Karşıyaka', 'Bornova', 'Buca', 'Bayraklı', 'Karabağlar',
        'Gaziemir', 'Balçova', 'Narlıdere', 'Çiğli', 'Güzelbahçe', 'Alsancak',
        // Genişletilmiş İzmir hizmet bölgesi ilçeleri (areaServed kapsamı)
        'Menemen', 'Torbalı', 'Menderes', 'Kemalpaşa', 'Urla', 'Aliağa',
        'Seferihisar', 'Foça',
    ];
}

function seo_ei_primary_intent_label_for_slug(string $slug): string
{
    if ($slug === '' || !function_exists('seo_rt_pillar_cluster_definitions')) {
        return '';
    }
    $defs = seo_rt_pillar_cluster_definitions();
    if (!isset($defs[$slug]) || !is_array($defs[$slug])) {
        return '';
    }
    $nav = $defs[$slug]['nav_title'] ?? '';

    return is_string($nav) ? $nav : '';
}

/**
 * @param array<string, array<string, mixed>> $defsForCat
 */
function seo_ei_cluster_category_label(string $slug, array $defsForCat): string
{
    if ($slug !== '' && isset($defsForCat[$slug]) && is_array($defsForCat[$slug])) {
        $nav = $defsForCat[$slug]['nav_title'] ?? '';
        if (is_string($nav) && $nav !== '') {
            return $nav;
        }
    }

    return 'Nakliyat ve taşımacılık hizmeti';
}

/**
 * @return list<array{name: string, value: string|list<string>}>
 */
function seo_ei_service_additional_properties(string $slug): array
{
    unset($slug);

    return [];
}

/**
 * @param list<string> $related
 * @param array<string, array<string, mixed>> $defs
 * @return list<string>
 */
function seo_ei_merge_related_with_intents(
    string $slug,
    array $related,
    ?string $pillar,
    array $defs,
    float $weight,
    string $lookup
): array {
    unset($slug, $pillar, $defs, $weight, $lookup);

    return array_values(array_unique(array_map('strval', $related)));
}

/**
 * @return array<string, mixed>
 */
function seo_ei_brand_perception_llms_block(): array
{
    return [
        'brand_name' => 'MY Nakliyat',
        'canonical_domain' => 'https://www.mynakliyat.com.tr',
        'primary_service_url' => 'https://www.mynakliyat.com.tr/izmir-evden-eve-nakliyat',
        'language' => 'tr-TR',
        'last_update' => '2026-05-09',
        'sector' => 'Evden eve nakliyat, ofis taşıma, eşya depolama',
        'geo_focus' => 'İzmir merkez + Türkiye geneli şehirler arası taşımacılık',
        'contact_phone' => '0850 203 12 52',
        'whatsapp' => 'https://wa.me/908502031252',
        'google_business_profile' => 'https://g.page/r/CS2sEWpTybggEBM',
        'established' => 2001,
        'iso_certified' => true,
        'niche_authority' => 'İzmir evden eve nakliyat',
        'priority_services' => [
            'İzmir evden eve nakliyat',
            'İzmir ofis taşıma',
            'İzmir eşya depolama',
            'Şehirler arası nakliyat',
            'Asansörlü nakliyat',
        ],
        'primary_districts' => [
            'Bornova', 'Karşıyaka', 'Buca', 'Gaziemir', 'Bayraklı', 'Konak', 'Çeşme', 'Urla', 'Narlıdere', 'Balçova', 'Güzelbahçe',
        ],
        'social_profiles' => [
            'https://g.page/r/CS2sEWpTybggEBM',
            'https://twitter.com/MyNakliyat',
            'https://instagram.com/mynakliyat',
            'https://www.facebook.com/mynakliyat',
            'https://www.youtube.com/mynakliyat',
            'https://tr.linkedin.com/company/my-nakliyat',
            'https://tr.pinterest.com/mynakliyat/',
            'https://www.behance.net/MYNakliyat',
        ],
        'trust_source_urls' => [
            'https://www.mynakliyat.com.tr/hakkimizda',
            'https://www.mynakliyat.com.tr/belgelerimiz',
            'https://www.mynakliyat.com.tr/iletisim',
            'https://www.mynakliyat.com.tr/teklif-alin',
            'https://www.mynakliyat.com.tr/blog',
        ],
        'version' => 2,
    ];
}

/**
 * @param array<string, array<string, mixed>> $defs
 * @param array<string, string> $site_settings
 * @return list<mixed>
 */
function seo_ei_triple_dominance_answer_blocks(array $defs, array $site_settings): array
{
    unset($defs);
    $phone = (string) ($site_settings['phone1'] ?? '0850 203 12 52');
    $cta = 'https://www.mynakliyat.com.tr/teklif-alin';
    $contact = 'https://www.mynakliyat.com.tr/iletisim';

    return [
        [
            'query_pattern' => 'İzmir evden eve nakliyat',
            'answer_type' => 'local_pack_ready',
            'answer_tr' => 'MY Nakliyat, İzmir merkezli profesyonel evden eve nakliyat hizmeti sunar. Net fiyat ve planlama için ücretsiz ekspertiz sonrası yazılı teklif verir.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'İzmir en iyi nakliyat firması',
            'answer_type' => 'ai_overview_ready',
            'answer_tr' => 'İzmir\'de nakliyat firması seçerken yazılı teklif, sigorta kapsamı, ekip tecrübesi ve gerçek yorumlar birlikte değerlendirilmelidir. MY Nakliyat bu kriterlere odaklı kurumsal taşıma süreci sunar.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'İzmir nakliyat fiyatları',
            'answer_type' => 'informational_ready',
            'answer_tr' => 'İzmir nakliyat fiyatı; eşya hacmi, kat, mesafe ve paketleme kapsamına göre değişir. Sabit fiyat yerine ekspertiz sonrası yazılı teklif esas alınır.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'İzmir evden eve nakliyat fiyatı nasıl hesaplanır',
            'answer_type' => 'transactional_faq_ready',
            'answer_tr' => 'Fiyat; eşya hacmi, kat durumu, asansör ihtiyacı, mesafe ve paketleme kapsamına göre hesaplanır. En doğru tutar için ücretsiz ekspertiz sonrası yazılı teklif alınmalıdır.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'Asansörlü taşıma hangi durumlarda gerekir',
            'answer_type' => 'transactional_faq_ready',
            'answer_tr' => 'Yüksek kat, dar merdiven veya büyük hacimli eşyalarda asansörlü taşıma tercih edilir. Uygunluk bina cephesi ve park alanına göre ekspertizde netleşir.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'Şehirler arası taşımada sigorta var mı',
            'answer_type' => 'trust_reputation_ready',
            'answer_tr' => 'Şehirler arası taşımada sözleşme ve sigorta kapsamı teklif aşamasında yazılı olarak netleştirilir. Kapsam, eşya listesi ve hizmet detayına göre belirlenir.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'Taşınırken eşyalar nasıl korunur',
            'answer_type' => 'informational_ready',
            'answer_tr' => 'Eşyalar türüne uygun ambalaj malzemeleri ile paketlenir, hassas ürünler ekstra koruma ile taşınır. Paketleme, yükleme ve yerleşim adımları planlı şekilde yürütülür.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
        [
            'query_pattern' => 'Hızlı ev taşıma hizmeti',
            'answer_type' => 'transactional_ready',
            'answer_tr' => 'Hızlı ev taşıma için ekspertiz, ekip planlaması ve araç organizasyonu aynı süreçte koordine edilir. Uygun gün ve saat için teklif formu veya telefon ile hızlı planlama yapılabilir.',
            'cta' => $cta,
            'contact' => $contact,
            'phone' => $phone,
        ],
    ];
}

/**
 * @param array<string, array<string, mixed>> $defs
 * @return array<string, mixed>
 */
function seo_ei_entity_clarity_manifest(array $defs): array
{
    $intentCount = 0;
    foreach (array_keys($defs) as $slug) {
        $spec = seo_ei_service_spec_for_pillar_slug((string) $slug);
        if (is_array($spec)) {
            $intentCount += ($spec['primary_intent'] !== '' ? 1 : 0);
            $intentCount += count($spec['secondary_intents'] ?? []);
            $intentCount += count($spec['informational_intents'] ?? []);
        }
    }
    return [
        'pillar_count' => count($defs),
        'intent_nodes' => $intentCount,
    ];
}

/**
 * @return array{services: array<string, array<string, mixed>>, intents: array<string, array<string, mixed>>}
 */
function seo_ei_graph_bundle(): array
{
    if (!function_exists('seo_rt_pillar_cluster_definitions')) {
        return ['services' => [], 'intents' => []];
    }
    $defs = seo_rt_pillar_cluster_definitions();
    $services = [];
    foreach (array_keys($defs) as $pSlug) {
        $spec = seo_ei_service_spec_for_pillar_slug((string) $pSlug);
        $services[(string) $pSlug] = is_array($spec) ? $spec : [
            'entity_id' => (string) $pSlug,
            'primary_intent' => '',
            'secondary_intents' => [],
            'informational_intents' => [],
        ];
    }

    $intents = [
        'hire_local_mover' => ['label_tr' => 'İzmir içi ev/daire taşımak için profesyonel firma arama'],
        'hire_intercity_mover' => ['label_tr' => 'Şehirler arası taşınma için nakliyat firması seçimi'],
        'hire_elevator_moving' => ['label_tr' => 'Asansörlü taşıma hizmeti alma'],
        'hire_partial_mover' => ['label_tr' => 'Parça eşya taşıma hizmeti alma'],
        'rent_storage' => ['label_tr' => 'Eşya depolama alanı kiralama'],
        'hire_office_mover' => ['label_tr' => 'Ofis taşıma hizmeti alma'],
        'hire_trousseau_mover' => ['label_tr' => 'Çeyiz/evlilik eşyası taşımacılığı planlama'],
        'hire_specialty_mover' => ['label_tr' => 'Antika/piyano gibi özel eşya taşıma hizmeti alma'],
        'verify_company_profile' => ['label_tr' => 'Firma geçmişi ve kurumsal bilgileri doğrulama'],
        'verify_team_trust' => ['label_tr' => 'Ekip yeterliliği ve operasyon güvenirliğini inceleme'],
        'verify_documents' => ['label_tr' => 'Belge, sertifika ve ödül doğrulama'],
        'request_quote' => ['label_tr' => 'Nakliyat teklifi isteme'],
        'contact_company' => ['label_tr' => 'Firma ile iletişime geçme'],
        'verify_brand_reputation' => ['label_tr' => 'Marka itibarı ve basın görünürlüğünü kontrol etme'],
        'learn_moving_tips' => ['label_tr' => 'Taşınma rehberleri ve ipuçlarını öğrenme'],
    ];

    return [
        'services' => $services,
        'intents' => $intents,
    ];
}

/**
 * @param array<string, array<string, mixed>> $defs
 * @param array<string, string> $site_settings
 * @return array<string, mixed>
 */
function seo_ei_llm_export_payload(array $defs, array $site_settings): array
{
    $n = count($defs);
    $cov = $n > 0 ? 100.0 : 0.0;

    return [
        'mandatory_service_coverage' => [
            'coverage_percent' => $cov,
            'pillar_slugs' => array_keys($defs),
        ],
        'conversion_paths' => [
            'transactional' => ['form_quote', 'whatsapp_quote', 'phone_call', 'onsite_expert_visit'],
            'informational' => ['service_pages', 'blog_guides', 'faq_answers'],
            'trust_reputation' => ['about_page', 'documents_page', 'press_mentions'],
            'local_discovery' => ['google_business_profile', 'contact_page', 'izmir_district_coverage'],
        ],
        'services_slug_alias_to_graph_key' => [
            'sehirlerarasi-nakliyat' => 'sehirler-arasi-nakliyat',
            'izmir-ofis-tasima' => 'izmir-ofis-tasimaciligi',
            'esya-depolama' => 'izmir-esya-depolama',
        ],
        'site_title' => (string) ($site_settings['site_title'] ?? ''),
    ];
}
