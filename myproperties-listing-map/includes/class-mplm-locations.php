<?php
/**
 * Sri Lanka 9 Provinces -> 25 Districts -> 309 ad_country Terms + Live Ad Counts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MPLM_Locations {

    const CACHE_KEY = 'mplm_live_counts_v1';
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Master structure of Sri Lanka Provinces, Districts, ISO codes, and MyProperties.lk ad_country term IDs.
     */
    public static function get_provinces() {
        return array(
            'western' => array(
                'slug'      => 'western',
                'name'      => 'Western Province',
                'districts' => array(
                    'colombo' => array(
                        'slug'             => 'colombo',
                        'name'             => 'Colombo',
                        'iso'              => 'LK11',
                        'district_term_id' => 492,
                        'towns'            => array( 492 => 'Colombo', 493 => 'Dehiwala', 494 => 'Moratuwa', 495 => 'Nugegoda', 496 => 'Maharagama', 497 => 'Sri Jayawardenepura Kotte', 498 => 'Rajagiriya', 499 => 'Kolonnawa', 500 => 'Kaduwela', 501 => 'Wellawatte', 502 => 'Bambalapitiya', 503 => 'Borella', 504 => 'Dematagoda', 505 => 'Maradana', 506 => 'Malabe', 507 => 'Battaramulla', 508 => 'Peliyagoda', 509 => 'Kotahena', 510 => 'Thimbirigasyaya', 511 => 'Homagama' ),
                    ),
                    'gampaha' => array(
                        'slug'             => 'gampaha',
                        'name'             => 'Gampaha',
                        'iso'              => 'LK12',
                        'district_term_id' => 512,
                        'towns'            => array( 512 => 'Gampaha', 513 => 'Negombo', 514 => 'Ja-Ela', 515 => 'Wattala', 516 => 'Kelaniya', 517 => 'Katunayake', 518 => 'Minuwangoda', 519 => 'Divulapitiya', 520 => 'Mirigama', 521 => 'Kiribathgoda', 522 => 'Kadawatha', 523 => 'Ragama', 524 => 'Nittambuwa', 525 => 'Veyangoda', 526 => 'Dompe', 527 => 'Biyagama', 528 => 'Seeduwa', 529 => 'Kochchikade', 530 => 'Udugampola', 531 => 'Ganemulla' ),
                    ),
                    'kalutara' => array(
                        'slug'             => 'kalutara',
                        'name'             => 'Kalutara',
                        'iso'              => 'LK13',
                        'district_term_id' => 533,
                        'towns'            => array( 533 => 'Kalutara', 534 => 'Horana', 535 => 'Bandaragama', 536 => 'Matugama', 537 => 'Beruwala', 538 => 'Aluthgama', 539 => 'Ingiriya', 540 => 'Wadduwa', 541 => 'Welipenna', 542 => 'Payagala', 543 => 'Maggona', 544 => 'Bulathsinhala', 545 => 'Dodangoda', 546 => 'Meegahathenna', 547 => 'Neboda', 548 => 'Millaniya', 549 => 'Morontuduwa', 550 => 'Kalawila', 551 => 'Pothupitiya', 825 => 'Panadura' ),
                    ),
                ),
            ),
            'central' => array(
                'slug'      => 'central',
                'name'      => 'Central Province',
                'districts' => array(
                    'kandy' => array(
                        'slug'             => 'kandy',
                        'name'             => 'Kandy',
                        'iso'              => 'LK21',
                        'district_term_id' => 592,
                        'towns'            => array( 592 => 'Kandy', 593 => 'Katugastota', 594 => 'Peradeniya', 595 => 'Gampola', 596 => 'Nawalapitiya', 597 => 'Teldeniya', 598 => 'Akurana', 599 => 'Kundasale', 600 => 'Digana', 601 => 'Galagedara', 602 => 'Poojapitiya', 603 => 'Harispattuwa', 604 => 'Menikhinna', 605 => 'Tennekumbura', 606 => 'Wattegama', 607 => 'Pathadumbara', 608 => 'Udunuwara', 609 => 'Panwila', 610 => 'Handessa', 611 => 'Hasalaka' ),
                    ),
                    'matale' => array(
                        'slug'             => 'matale',
                        'name'             => 'Matale',
                        'iso'              => 'LK22',
                        'district_term_id' => 612,
                        'towns'            => array( 612 => 'Matale', 613 => 'Dambulla', 614 => 'Galewela', 615 => 'Naula', 616 => 'Rattota', 617 => 'Ukuwela', 618 => 'Wilgamuwa', 619 => 'Pallepola', 620 => 'Yatawatta', 621 => 'Sigiriya' ),
                    ),
                    'nuwara-eliya' => array(
                        'slug'             => 'nuwara-eliya',
                        'name'             => 'Nuwara Eliya',
                        'iso'              => 'LK23',
                        'district_term_id' => 622,
                        'towns'            => array( 622 => 'Nuwara Eliya', 623 => 'Hatton', 624 => 'Talawakele', 625 => 'Kotagala', 626 => 'Lindula', 627 => 'Ragala', 628 => 'Walapane', 629 => 'Bogawantalawa', 630 => 'Watawala', 631 => 'Nallathanniya' ),
                    ),
                ),
            ),
            'southern' => array(
                'slug'      => 'southern',
                'name'      => 'Southern Province',
                'districts' => array(
                    'galle' => array(
                        'slug'             => 'galle',
                        'name'             => 'Galle',
                        'iso'              => 'LK31',
                        'district_term_id' => 552,
                        'towns'            => array( 552 => 'Galle', 553 => 'Hikkaduwa', 554 => 'Ambalangoda', 555 => 'Bentota', 556 => 'Karandeniya', 557 => 'Elpitiya', 558 => 'Baddegama', 559 => 'Unawatuna', 560 => 'Ahangama', 561 => 'Rathgama', 562 => 'Udugama', 563 => 'Imaduwa', 564 => 'Yakkalamulla', 565 => 'Thalgaswala', 566 => 'Batapola', 567 => 'Gonapinuwala', 568 => 'Pilana', 569 => 'Wanchawala', 570 => 'Habaraduwa', 571 => 'Koggala' ),
                    ),
                    'matara' => array(
                        'slug'             => 'matara',
                        'name'             => 'Matara',
                        'iso'              => 'LK32',
                        'district_term_id' => 572,
                        'towns'            => array( 572 => 'Matara', 573 => 'Weligama', 574 => 'Mirissa', 575 => 'Dickwella', 576 => 'Akuressa', 577 => 'Kamburupitiya', 578 => 'Devinuwara', 579 => 'Kotapola', 580 => 'Pasgoda', 581 => 'Thihagoda' ),
                    ),
                    'hambantota' => array(
                        'slug'             => 'hambantota',
                        'name'             => 'Hambantota',
                        'iso'              => 'LK33',
                        'district_term_id' => 582,
                        'towns'            => array( 582 => 'Hambantota', 583 => 'Tangalle', 584 => 'Tissamaharama', 585 => 'Ambalantota', 586 => 'Beliatta', 587 => 'Weeraketiya', 588 => 'Suriyawewa', 589 => 'Kataragama', 590 => 'Lunugamvehera', 591 => 'Angunakolapelessa' ),
                    ),
                ),
            ),
            'north-western' => array(
                'slug'      => 'north-western',
                'name'      => 'North Western Province',
                'districts' => array(
                    'kurunegala' => array(
                        'slug'             => 'kurunegala',
                        'name'             => 'Kurunegala',
                        'iso'              => 'LK61',
                        'district_term_id' => 732,
                        'towns'            => array( 732 => 'Kurunegala', 733 => 'Kuliyapitiya', 734 => 'Narammala', 735 => 'Pannala', 736 => 'Wariyapola', 737 => 'Nikaweratiya', 738 => 'Alawwa', 739 => 'Polgahawela', 740 => 'Melsiripura', 741 => 'Mawathagama', 742 => 'Bingiriya', 743 => 'Galgamuwa', 744 => 'Rideegama', 745 => 'Ibbagamuwa', 746 => 'Kobeigane', 747 => 'Weerambugedara', 748 => 'Udubaddawa', 749 => 'Rasnayakapura', 750 => 'Hiripitiya', 751 => 'Giribawa' ),
                    ),
                    'puttalam' => array(
                        'slug'             => 'puttalam',
                        'name'             => 'Puttalam',
                        'iso'              => 'LK62',
                        'district_term_id' => 752,
                        'towns'            => array( 752 => 'Puttalam', 753 => 'Chilaw', 754 => 'Wennappuwa', 755 => 'Marawila', 756 => 'Nattandiya', 757 => 'Mundel', 758 => 'Anamaduwa', 759 => 'Dankotuwa', 760 => 'Kalpitiya', 761 => 'Pallama' ),
                    ),
                ),
            ),
            'sabaragamuwa' => array(
                'slug'      => 'sabaragamuwa',
                'name'      => 'Sabaragamuwa Province',
                'districts' => array(
                    'ratnapura' => array(
                        'slug'             => 'ratnapura',
                        'name'             => 'Ratnapura',
                        'iso'              => 'LK91',
                        'district_term_id' => 762,
                        'towns'            => array( 762 => 'Ratnapura', 763 => 'Balangoda', 764 => 'Embilipitiya', 765 => 'Pelmadulla', 766 => 'Kuruwita', 767 => 'Kalawana', 768 => 'Godakawela', 769 => 'Rakwana', 770 => 'Eheliyagoda', 771 => 'Ayagama' ),
                    ),
                    'kegalle' => array(
                        'slug'             => 'kegalle',
                        'name'             => 'Kegalle',
                        'iso'              => 'LK92',
                        'district_term_id' => 772,
                        'towns'            => array( 772 => 'Kegalle', 773 => 'Mawanella', 774 => 'Rambukkana', 775 => 'Warakapola', 776 => 'Ruwanwella', 777 => 'Dehiowita', 778 => 'Yatiyantota', 779 => 'Aranayake', 780 => 'Bulathkohupitiya', 781 => 'Galigamuwa' ),
                    ),
                ),
            ),
            'north-central' => array(
                'slug'      => 'north-central',
                'name'      => 'North Central Province',
                'districts' => array(
                    'anuradhapura' => array(
                        'slug'             => 'anuradhapura',
                        'name'             => 'Anuradhapura',
                        'iso'              => 'LK71',
                        'district_term_id' => 712,
                        'towns'            => array( 712 => 'Anuradhapura', 713 => 'Medawachchiya', 714 => 'Thalawa', 715 => 'Mihintale', 716 => 'Kebithigollewa', 717 => 'Horowpothana', 718 => 'Galenbindunuwewa', 719 => 'Padaviya', 720 => 'Nachchaduwa', 721 => 'Ipalogama' ),
                    ),
                    'polonnaruwa' => array(
                        'slug'             => 'polonnaruwa',
                        'name'             => 'Polonnaruwa',
                        'iso'              => 'LK72',
                        'district_term_id' => 722,
                        'towns'            => array( 722 => 'Polonnaruwa', 723 => 'Hingurakgoda', 724 => 'Medirigiriya', 725 => 'Kaduruwela', 726 => 'Lankapura', 727 => 'Welikanda', 728 => 'Dimbulagala', 729 => 'Bakamuna', 730 => 'Elahera', 731 => 'Somawathiya' ),
                    ),
                ),
            ),
            'uva' => array(
                'slug'      => 'uva',
                'name'      => 'Uva Province',
                'districts' => array(
                    'badulla' => array(
                        'slug'             => 'badulla',
                        'name'             => 'Badulla',
                        'iso'              => 'LK81',
                        'district_term_id' => 782,
                        'towns'            => array( 782 => 'Badulla', 783 => 'Bandarawela', 784 => 'Ella', 785 => 'Haputale', 786 => 'Mahiyanganaya', 787 => 'Passara', 788 => 'Welimada', 789 => 'Hali Ela', 790 => 'Lunugala', 791 => 'Rideemaliyadda' ),
                    ),
                    'monaragala' => array(
                        'slug'             => 'monaragala',
                        'name'             => 'Monaragala',
                        'iso'              => 'LK82',
                        'district_term_id' => 792,
                        'towns'            => array( 792 => 'Monaragala', 793 => 'Wellawaya', 794 => 'Bibile', 795 => 'Buttala', 796 => 'Medagama', 797 => 'Siyambalanduwa', 798 => 'Badalkumbura', 799 => 'Thanamalwila', 800 => 'Okkampitiya' ),
                    ),
                ),
            ),
            'eastern' => array(
                'slug'      => 'eastern',
                'name'      => 'Eastern Province',
                'districts' => array(
                    'trincomalee' => array(
                        'slug'             => 'trincomalee',
                        'name'             => 'Trincomalee',
                        'iso'              => 'LK53',
                        'district_term_id' => 682,
                        'towns'            => array( 682 => 'Trincomalee', 683 => 'Kinniya', 684 => 'Kantale', 685 => 'Muttur', 686 => 'Nilaveli', 687 => 'China Bay', 688 => 'Thampalakamam', 689 => 'Seruvila', 690 => 'Kuchchaveli', 691 => 'Morawewa' ),
                    ),
                    'batticaloa' => array(
                        'slug'             => 'batticaloa',
                        'name'             => 'Batticaloa',
                        'iso'              => 'LK51',
                        'district_term_id' => 692,
                        'towns'            => array( 692 => 'Batticaloa', 693 => 'Kattankudy', 694 => 'Eravur', 695 => 'Valaichenai', 696 => 'Kaluwanchikudy', 697 => 'Oddamavadi', 698 => 'Chenkalady', 699 => 'Kiran', 700 => 'Manmunai', 701 => 'Paddiruppu' ),
                    ),
                    'ampara' => array(
                        'slug'             => 'ampara',
                        'name'             => 'Ampara',
                        'iso'              => 'LK52',
                        'district_term_id' => 702,
                        'towns'            => array( 702 => 'Ampara', 703 => 'Kalmunai', 704 => 'Akkaraipattu', 705 => 'Sainthamaruthu', 706 => 'Sammanthurai', 707 => 'Pottuvil', 708 => 'Uhana', 709 => 'Maha Oya', 710 => 'Dehiattakandiya', 711 => 'Lahugala' ),
                    ),
                ),
            ),
            'northern' => array(
                'slug'      => 'northern',
                'name'      => 'Northern Province',
                'districts' => array(
                    'jaffna' => array(
                        'slug'             => 'jaffna',
                        'name'             => 'Jaffna',
                        'iso'              => 'LK41',
                        'district_term_id' => 632,
                        'towns'            => array( 632 => 'Jaffna', 633 => 'Nallur', 634 => 'Chavakachcheri', 635 => 'Point Pedro', 636 => 'Kopay', 637 => 'Karainagar', 638 => 'Uduvil', 639 => 'Sandilipay', 640 => 'Velanai', 641 => 'Kankesanthurai' ),
                    ),
                    'kilinochchi' => array(
                        'slug'             => 'kilinochchi',
                        'name'             => 'Kilinochchi',
                        'iso'              => 'LK42',
                        'district_term_id' => 642,
                        'towns'            => array( 642 => 'Kilinochchi', 643 => 'Pallai', 644 => 'Paranthan', 645 => 'Kandavalai', 646 => 'Pooneryn', 647 => 'Karachchi', 648 => 'Murukandi', 649 => 'Akkarayankulam', 650 => 'Iranamadu', 651 => 'Nachchikuda' ),
                    ),
                    'mannar' => array(
                        'slug'             => 'mannar',
                        'name'             => 'Mannar',
                        'iso'              => 'LK43',
                        'district_term_id' => 652,
                        'towns'            => array( 652 => 'Mannar', 653 => 'Pesalai', 654 => 'Madhu', 655 => 'Thalaimannar', 656 => 'Musali', 657 => 'Nanaddan', 658 => 'Mantai', 659 => 'Pallimunai', 660 => 'Adampan', 661 => 'Silavathurai' ),
                    ),
                    'vavuniya' => array(
                        'slug'             => 'vavuniya',
                        'name'             => 'Vavuniya',
                        'iso'              => 'LK44',
                        'district_term_id' => 672,
                        'towns'            => array( 672 => 'Vavuniya', 673 => 'Vavuniya South', 674 => 'Nedunkeni', 675 => 'Cheddikulam', 676 => 'Settikulam', 677 => 'Puliyankulam', 678 => 'Poovarasankulam', 679 => 'Navvi', 680 => 'Ulukkulam', 681 => 'Pampaimadu' ),
                    ),
                    'mullaitivu' => array(
                        'slug'             => 'mullaitivu',
                        'name'             => 'Mullaitivu',
                        'iso'              => 'LK45',
                        'district_term_id' => 662,
                        'towns'            => array( 662 => 'Mullaitivu', 663 => 'Puthukkudiyiruppu', 664 => 'Oddusuddan', 665 => 'Maritimepattu', 666 => 'Thunukkai', 667 => 'Welioya', 668 => 'Alampil', 669 => 'Nayaru', 670 => 'Kokkilai', 671 => 'Mankulam' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Get flat map of district_term_id => array of all town term IDs in that district.
     */
    public static function get_district_term_expansion_map() {
        static $map = null;
        if ( null !== $map ) {
            return $map;
        }
        $map = array();
        foreach ( self::get_provinces() as $province ) {
            foreach ( $province['districts'] as $district ) {
                $ids = array_map( 'intval', array_keys( $district['towns'] ) );
                $map[ intval( $district['district_term_id'] ) ] = $ids;
            }
        }
        return $map;
    }

    /**
     * Return all town IDs belonging to a district term_id (or empty array if term_id is a town, not a district).
     */
    public static function get_town_ids_by_district_term_id( $term_id ) {
        $map = self::get_district_term_expansion_map();
        $term_id = intval( $term_id );
        if ( ! isset( $map[ $term_id ] ) ) {
            return array();
        }
        $ids = $map[ $term_id ];

        // Also include any WordPress child terms in case hierarchy is ever added later.
        $children = get_term_children( $term_id, 'ad_country' );
        if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
            $ids = array_values( array_unique( array_merge( $ids, array_map( 'intval', $children ) ) ) );
        }

        return $ids;
    }

    /**
     * Return all town IDs belonging to a district slug.
     */
    public static function get_town_ids_by_district_slug( $slug ) {
        $slug = sanitize_title( $slug );
        foreach ( self::get_provinces() as $province ) {
            if ( isset( $province['districts'][ $slug ] ) ) {
                return self::get_town_ids_by_district_term_id( $province['districts'][ $slug ]['district_term_id'] );
            }
        }
        return array();
    }

    /**
     * Clear cached live counts.
     */
    public static function clear_cache() {
        delete_transient( self::CACHE_KEY . '_all' );
        delete_transient( self::CACHE_KEY . '_active' );
    }

    /**
     * Build complete frontend data payload with live All / Sell / Rent counts.
     */
    public static function get_map_data( $only_active = false ) {
        $cache_key = self::CACHE_KEY . ( $only_active ? '_active' : '_all' );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        global $wpdb;

        $status_join = '';
        if ( $only_active ) {
            $status_join = "INNER JOIN {$wpdb->postmeta} pm_status ON (p.ID = pm_status.post_id AND pm_status.meta_key = '_adforest_ad_status_' AND pm_status.meta_value = 'active')";
        }

        // Single fast SQL query to get every published ad_post with its ad_country term_id and _adforest_ad_type meta
        $sql = "
            SELECT tt.term_id, p.ID AS post_id, COALESCE(pm_type.meta_value, '') AS ad_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'ad_country')
            LEFT JOIN {$wpdb->postmeta} pm_type ON (p.ID = pm_type.post_id AND pm_type.meta_key = '_adforest_ad_type')
            {$status_join}
            WHERE p.post_type = 'ad_post'
              AND p.post_status = 'publish'
        ";

        $rows = $wpdb->get_results( $sql );

        // Track unique post IDs per town term_id to prevent double counting
        $town_posts = array();
        $site_posts = array(
            'all'  => array(),
            'Sell' => array(),
            'Rent' => array(),
        );

        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $tid     = intval( $row->term_id );
                $pid     = intval( $row->post_id );
                $ad_type = trim( (string) $row->ad_type );

                if ( ! isset( $town_posts[ $tid ] ) ) {
                    $town_posts[ $tid ] = array(
                        'all'  => array(),
                        'Sell' => array(),
                        'Rent' => array(),
                    );
                }

                $town_posts[ $tid ]['all'][ $pid ] = true;
                $site_posts['all'][ $pid ]         = true;

                if ( strcasecmp( $ad_type, 'Sell' ) === 0 ) {
                    $town_posts[ $tid ]['Sell'][ $pid ] = true;
                    $site_posts['Sell'][ $pid ]         = true;
                } elseif ( strcasecmp( $ad_type, 'Rent' ) === 0 ) {
                    $town_posts[ $tid ]['Rent'][ $pid ] = true;
                    $site_posts['Rent'][ $pid ]         = true;
                }
            }
        }

        // Fetch fresh term names and any child town terms created in WordPress under district terms.
        $live_names     = array();
        $dynamic_towns  = array();
        $district_by_id = array();
        foreach ( self::get_provinces() as $p_slug => $province ) {
            foreach ( $province['districts'] as $d_slug => $district ) {
                $district_by_id[ intval( $district['district_term_id'] ) ] = array(
                    'province' => $p_slug,
                    'district' => $d_slug,
                );
            }
        }

        $wp_terms = get_terms( array(
            'taxonomy'   => 'ad_country',
            'hide_empty' => false,
        ) );
        if ( ! is_wp_error( $wp_terms ) && ! empty( $wp_terms ) ) {
            foreach ( $wp_terms as $t ) {
                $tid = intval( $t->term_id );
                $live_names[ $tid ] = $t->name;

                $parent_id = intval( $t->parent );
                if ( $parent_id > 0 && isset( $district_by_id[ $parent_id ] ) ) {
                    $d_slug = $district_by_id[ $parent_id ]['district'];
                    if ( ! isset( $dynamic_towns[ $d_slug ] ) ) {
                        $dynamic_towns[ $d_slug ] = array();
                    }
                    $dynamic_towns[ $d_slug ][ $tid ] = $t->name;
                }
            }
        }

        $provinces_out = array();
        $districts_out = array();

        foreach ( self::get_provinces() as $p_slug => $province ) {
            $prov_posts = array(
                'all'  => array(),
                'Sell' => array(),
                'Rent' => array(),
            );
            $prov_district_slugs = array();

            foreach ( $province['districts'] as $d_slug => $district ) {
                $dist_posts = array(
                    'all'  => array(),
                    'Sell' => array(),
                    'Rent' => array(),
                );
                $towns_out = array();

                $merged_towns = $district['towns'];
                if ( ! empty( $dynamic_towns[ $d_slug ] ) ) {
                    foreach ( $dynamic_towns[ $d_slug ] as $dyn_tid => $dyn_name ) {
                        if ( ! isset( $merged_towns[ $dyn_tid ] ) ) {
                            $merged_towns[ $dyn_tid ] = $dyn_name;
                        }
                    }
                }

                foreach ( $merged_towns as $tid => $default_name ) {
                    $tid       = intval( $tid );
                    $town_name = isset( $live_names[ $tid ] ) ? $live_names[ $tid ] : $default_name;

                    $t_all  = isset( $town_posts[ $tid ]['all'] ) ? $town_posts[ $tid ]['all'] : array();
                    $t_sell = isset( $town_posts[ $tid ]['Sell'] ) ? $town_posts[ $tid ]['Sell'] : array();
                    $t_rent = isset( $town_posts[ $tid ]['Rent'] ) ? $town_posts[ $tid ]['Rent'] : array();

                    // Merge unique post IDs into district & province buckets
                    foreach ( $t_all as $pid => $_ ) {
                        $dist_posts['all'][ $pid ] = true;
                        $prov_posts['all'][ $pid ] = true;
                    }
                    foreach ( $t_sell as $pid => $_ ) {
                        $dist_posts['Sell'][ $pid ] = true;
                        $prov_posts['Sell'][ $pid ] = true;
                    }
                    foreach ( $t_rent as $pid => $_ ) {
                        $dist_posts['Rent'][ $pid ] = true;
                        $prov_posts['Rent'][ $pid ] = true;
                    }

                    $towns_out[] = array(
                        'term_id'     => $tid,
                        'name'        => $town_name,
                        'is_district' => ( $tid === intval( $district['district_term_id'] ) ),
                        'counts'      => array(
                            'all'  => count( $t_all ),
                            'Sell' => count( $t_sell ),
                            'Rent' => count( $t_rent ),
                        ),
                    );
                }

                // Sort towns: highest 'all' count first, then alphabetical
                usort( $towns_out, function ( $a, $b ) {
                    if ( $b['counts']['all'] !== $a['counts']['all'] ) {
                        return $b['counts']['all'] - $a['counts']['all'];
                    }
                    return strcasecmp( $a['name'], $b['name'] );
                } );

                $districts_out[ $d_slug ] = array(
                    'slug'             => $d_slug,
                    'name'             => $district['name'],
                    'iso'              => $district['iso'],
                    'province_slug'    => $p_slug,
                    'province_name'    => $province['name'],
                    'district_term_id' => intval( $district['district_term_id'] ),
                    'counts'           => array(
                        'all'  => count( $dist_posts['all'] ),
                        'Sell' => count( $dist_posts['Sell'] ),
                        'Rent' => count( $dist_posts['Rent'] ),
                    ),
                    'towns'            => $towns_out,
                );

                $prov_district_slugs[] = $d_slug;
            }

            $provinces_out[] = array(
                'slug'      => $p_slug,
                'name'      => $province['name'],
                'districts' => $prov_district_slugs,
                'counts'    => array(
                    'all'  => count( $prov_posts['all'] ),
                    'Sell' => count( $prov_posts['Sell'] ),
                    'Rent' => count( $prov_posts['Rent'] ),
                ),
            );
        }

        $data = array(
            'totals'    => array(
                'all'  => count( $site_posts['all'] ),
                'Sell' => count( $site_posts['Sell'] ),
                'Rent' => count( $site_posts['Rent'] ),
            ),
            'provinces' => $provinces_out,
            'districts' => $districts_out,
        );

        set_transient( $cache_key, $data, self::CACHE_TTL );
        return $data;
    }
}
