<?php
/**
 * Plugin Name: Jirawat Site Setup (ร้านจิรวัสสังฆภัณฑ์ โคราช)
 * Description: กดเปิดใช้งานครั้งเดียว ระบบจะสร้างหน้าเว็บ เมนู รูปภาพ คลิปวิดีโอ ฟอนต์ และดีไซน์ของร้านจิรวัสสังฆภัณฑ์ให้อัตโนมัติ (โครงงานรายวิชา 1101023 / 1101033)
 * Version: 2.0.0
 * Author: กลุ่มโครงงาน 1101023/1101033
 * Text Domain: jirawat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JIRAWAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'JIRAWAT_URL', plugin_dir_url( __FILE__ ) );
define( 'JIRAWAT_VER', '2.0.0' );

register_activation_hook( __FILE__, 'jirawat_setup_run' );

/* ---------- ฟอนต์ + ดีไซน์ (โหลดทุกหน้า และในหน้าแก้ไข) ---------- */
function jirawat_enqueue_styles() {
	wp_enqueue_style( 'jirawat-fonts', JIRAWAT_URL . 'assets/fonts.css', array(), JIRAWAT_VER );
	wp_enqueue_style( 'jirawat-site', JIRAWAT_URL . 'assets/site.css', array( 'jirawat-fonts' ), (string) filemtime( JIRAWAT_DIR . 'assets/site.css' ) );
}
add_action( 'wp_enqueue_scripts', 'jirawat_enqueue_styles', 20 );
add_action( 'enqueue_block_assets', 'jirawat_enqueue_styles', 20 );
add_action( 'wp_head', function () {
	echo '<link rel="preload" href="' . esc_url( JIRAWAT_URL . 'assets/fonts/Trirong-700-thai.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<meta name="theme-color" content="#FBF6EC">' . "\n";
}, 1 );

/* ---------- Dashboard ผลสำรวจ: ใส่ [jirawat_dashboard] ในหน้าใดก็ได้ ----------
 * ใช้ assets/survey.csv (ข้อมูลจริง) ถ้ามี ไม่งั้นใช้ assets/survey_SIMULATED.csv พร้อมป้ายเตือน */
function jirawat_dashboard_shortcode() {
	$real = file_exists( JIRAWAT_DIR . 'assets/survey.csv' );
	$csv  = JIRAWAT_URL . 'assets/' . ( $real ? 'survey.csv' : 'survey_SIMULATED.csv' );
	wp_enqueue_script( 'chartjs', JIRAWAT_URL . 'assets/chart.umd.min.js', array(), '4.4.1', true );
	wp_enqueue_script( 'jirawat-dashboard', JIRAWAT_URL . 'assets/dashboard.js', array( 'chartjs' ), (string) filemtime( JIRAWAT_DIR . 'assets/dashboard.js' ), true );
	$card = function ( $id, $title, $wide = '' ) {
		return '<figure class="jw-chart' . ( $wide ? ' is-' . $wide : '' ) . '"><figcaption>' . esc_html( $title ) . '</figcaption><div class="jw-chart-box"><canvas id="' . esc_attr( $id ) . '" role="img" aria-label="' . esc_attr( $title ) . '"></canvas></div></figure>';
	};
	ob_start();
	?>
	<div id="jw-dash" class="jw-dash alignwide" data-csv="<?php echo esc_url( $csv ); ?>" data-schema="<?php echo esc_url( JIRAWAT_URL . 'assets/survey_schema.json' ); ?>">
		<?php if ( ! $real ) : ?>
			<p class="jw-dash-flag"><strong>ข้อมูลจำลอง</strong> หน้านี้ใช้ข้อมูลตัวอย่างเพื่อแสดงรูปแบบ Dashboard เท่านั้น ยังไม่ใช่ผลสำรวจลูกค้าจริง</p>
		<?php endif; ?>
		<div class="jw-dash-bar">
			<label>เพศ <select id="jw-f-gender"><option value="">ทั้งหมด</option><option>ชาย</option><option>หญิง</option></select></label>
			<label>อายุ <select id="jw-f-age"><option value="">ทุกช่วงอายุ</option></select></label>
			<button type="button" id="jw-f-reset">ล้างตัวกรอง</button>
			<span id="jw-n-filter" class="jw-dash-count" aria-live="polite"></span>
		</div>
		<div class="jw-dash-body">
			<div class="jw-kpis">
				<div><span>ผู้ตอบแบบสอบถาม</span><strong data-kpi="n">–</strong><small data-kpi="gender"></small></div>
				<div><span>ความพึงพอใจภาพรวม</span><strong data-kpi="overall">–</strong><small>ระดับ <b data-kpi="overallLv"></b> (เต็ม 5)</small></div>
				<div><span>จะกลับมาซื้อซ้ำแน่นอน</span><strong data-kpi="repeat">–</strong><small>ของผู้ตอบทั้งหมด</small></div>
				<div><span>สนใจสั่งซื้อออนไลน์</span><strong data-kpi="online">–</strong><small>ตอบ "ใช้แน่นอน" หรือ "อาจจะใช้"</small></div>
			</div>

			<section class="jw-dash-sec"><h3>สรุปผลสำคัญ</h3><ul id="jw-summary" class="jw-summary"></ul></section>

			<section class="jw-dash-sec"><h3>ส่วนที่ 1 · ข้อมูลทั่วไปของผู้ตอบ</h3><div class="jw-grid">
				<?php echo $card( 'jw-c-gender', 'เพศ' ) . $card( 'jw-c-age', 'ช่วงอายุ' ) . $card( 'jw-c-area', 'ที่พักอาศัย' ) . $card( 'jw-c-occupation', 'อาชีพ', 'wide' ) . $card( 'jw-c-income', 'รายได้ต่อเดือน' ); ?>
			</div></section>

			<section class="jw-dash-sec"><h3>ส่วนที่ 2 · พฤติกรรมของลูกค้า</h3><div class="jw-grid">
				<?php
				echo $card( 'jw-c-freq', 'ความถี่ในการซื้อ' ) . $card( 'jw-c-product', 'ประเภทสินค้าที่ซื้อมากที่สุด', 'wide' ) .
					$card( 'jw-c-occasion', 'โอกาสที่ซื้อบ่อยที่สุด', 'wide' ) . $card( 'jw-c-spend', 'ค่าใช้จ่ายต่อครั้ง' ) .
					$card( 'jw-c-time', 'ช่วงเวลาที่มาร้าน' ) . $card( 'jw-c-with', 'มาซื้อกับใคร' ) . $card( 'jw-c-pay', 'ช่องทางชำระเงิน' ) .
					$card( 'jw-c-know', 'รู้จักร้านจากช่องทางใด', 'wide' ) . $card( 'jw-c-online', 'ถ้ามีช่องทางออนไลน์' ) .
					$card( 'jw-c-purpose', 'จุดประสงค์ในการซื้อ', 'wide' ) . $card( 'jw-c-repeat', 'การกลับมาซื้อซ้ำ' ) .
					$card( 'jw-c-factor', 'ปัจจัยที่เลือกซื้อร้านนี้', 'full' );
				?>
			</div></section>

			<section class="jw-dash-sec"><h3>ส่วนที่ 3 · ความพึงพอใจของลูกค้า</h3><div class="jw-grid">
				<?php echo $card( 'jw-c-aspect', 'คะแนนเฉลี่ยรายด้าน (เต็ม 5)', 'wide' ) . $card( 'jw-c-rank', 'ด้านที่ถูกจัดเป็นอันดับ 1 (คน)' ) . $card( 'jw-c-stack', 'สัดส่วนระดับความพึงพอใจในแต่ละด้าน', 'full' ); ?>
			</div>
			<div class="jw-table-wrap" id="jw-t-sat"></div>
			<p class="jw-note">เกณฑ์แปลผล: 4.21–5.00 มากที่สุด · 3.41–4.20 มาก · 2.61–3.40 ปานกลาง · 1.81–2.60 น้อย · 1.00–1.80 น้อยที่สุด</p>
			</section>

			<section class="jw-dash-sec"><h3>ส่วนที่ 4 · ข้อเสนอแนะจากลูกค้า</h3><ul id="jw-sugg" class="jw-sugg"></ul></section>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'jirawat_dashboard', 'jirawat_dashboard_shortcode' );

/* ---------- ไอคอนเส้น (SVG) ---------- */
function jirawat_icon( $name ) {
	$p = array(
		'pin'   => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'phone' => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'chat'  => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'lotus' => '<path d="M12 20c-4 0-8-2.5-9-6 3 0 5.5 1 7 3"/><path d="M12 20c4 0 8-2.5 9-6-3 0-5.5 1-7 3"/><path d="M12 20c-2.5-2.5-3.5-5.5-3-9 1.5 1 2.6 2.4 3 4 .4-1.6 1.5-3 3-4 .5 3.5-.5 6.5-3 9z"/><path d="M12 11c-1-2.2-1-4.6 0-7 1 2.4 1 4.8 0 7z"/>',
		'hands' => '<path d="M12 21V9.5L10 4a1.5 1.5 0 0 0-2.8 1L8.5 10 6 13v5l3 3"/><path d="M12 21V9.5L14 4a1.5 1.5 0 0 1 2.8 1L15.5 10 18 13v5l-3 3"/>',
		'route' => '<circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M8 19h7.5a3.5 3.5 0 0 0 0-7h-7a3.5 3.5 0 0 1 0-7H16"/>',
		'play'  => '<circle cx="12" cy="12" r="10"/><path d="M10 8.5v7l6-3.5z"/>',
		'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
	);
	return '<svg class="jw-ico" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}

/** คัดลอกไฟล์จากโฟลเดอร์ assets ของปลั๊กอินเข้า Media Library แล้วคืนค่า URL */
function jirawat_import_asset( $filename, $title ) {
	$src = JIRAWAT_DIR . 'assets/' . $filename;
	if ( ! file_exists( $src ) ) {
		return '';
	}
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_jirawat_asset',
		'meta_value'  => $filename,
		'numberposts' => 1,
	) );
	if ( $existing ) {
		return wp_get_attachment_url( $existing[0]->ID );
	}
	$upload = wp_upload_bits( $filename, null, file_get_contents( $src ) );
	if ( ! empty( $upload['error'] ) ) {
		return '';
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => $title,
		'post_status'    => 'inherit',
	), $upload['file'] );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_jirawat_asset', $filename );
	return $upload['url'];
}

function jirawat_upsert_page( $slug, $title, $content, $order ) {
	$page = get_page_by_path( $slug );
	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'menu_order'   => $order,
	);
	if ( $page ) {
		$data['ID'] = $page->ID;
		return wp_update_post( $data );
	}
	return wp_insert_post( $data );
}

/* ---------- ตัวช่วยสร้างบล็อก ---------- */
function jw_p( $html, $class = '' ) {
	$a = $class ? '{"className":"' . $class . '"} ' : '';
	$c = $class ? ' class="' . $class . '"' : '';
	return '<!-- wp:paragraph ' . $a . '--><p' . $c . '>' . $html . '</p><!-- /wp:paragraph -->';
}
function jw_h( $text, $level = 2, $class = '' ) {
	$attrs = array();
	if ( 2 !== $level ) {
		$attrs['level'] = $level;
	}
	if ( $class ) {
		$attrs['className'] = $class;
	}
	$a = $attrs ? wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' ' : '';
	return '<!-- wp:heading ' . $a . '--><h' . $level . ' class="wp-block-heading' . ( $class ? ' ' . $class : '' ) . '">' . $text . '</h' . $level . '><!-- /wp:heading -->';
}
function jw_group( $inner, $class, $align = 'full' ) {
	return '<!-- wp:group {"align":"' . $align . '","className":"' . $class . '","layout":{"type":"constrained"}} --><div class="wp-block-group align' . $align . ' ' . $class . '">' . $inner . '</div><!-- /wp:group -->';
}
function jw_html( $html ) {
	return '<!-- wp:html -->' . $html . '<!-- /wp:html -->';
}
function jw_img( $url, $alt, $class = '' ) {
	if ( ! $url ) {
		return '';
	}
	$cls = 'wp-block-image size-large' . ( $class ? ' ' . $class : '' );
	$a   = $class ? ',"className":"' . $class . '"' : '';
	return '<!-- wp:image {"sizeSlug":"large"' . $a . '} --><figure class="' . $cls . '"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"/></figure><!-- /wp:image -->';
}
function jw_btn( $label, $href, $style = 'fill', $external = false ) {
	$t = $external ? ' target="_blank" rel="noreferrer noopener"' : '';
	$c = 'outline' === $style ? 'is-style-outline' : 'jw-btn-fill';
	return '<!-- wp:button {"className":"' . $c . '"} --><div class="wp-block-button ' . $c . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $href ) . '"' . $t . '>' . $label . '</a></div><!-- /wp:button -->';
}
function jw_btns( $inner ) {
	return '<!-- wp:buttons --><div class="wp-block-buttons">' . $inner . '</div><!-- /wp:buttons -->';
}
function jw_gallery( $urls, $class = 'jw-mosaic' ) {
	$urls = array_values( array_filter( $urls ) );
	if ( ! $urls ) {
		return '';
	}
	$out = '<!-- wp:gallery {"columns":3,"linkTo":"none","align":"wide","className":"' . $class . '"} --><figure class="wp-block-gallery alignwide has-nested-images columns-3 is-cropped ' . $class . '">';
	foreach ( $urls as $u ) {
		$out .= '<!-- wp:image {"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img src="' . esc_url( $u ) . '" alt="บรรยากาศภายในร้านจิรวัสสังฆภัณฑ์"/></figure><!-- /wp:image -->';
	}
	return $out . '</figure><!-- /wp:gallery -->';
}
function jw_video( $url, $poster = '' ) {
	if ( ! $url ) {
		return jw_p( '<em>(ยังไม่มีไฟล์คลิป: วางไฟล์ promo.mp4 ไว้ในโฟลเดอร์ assets ของปลั๊กอิน แล้วปิด-เปิดปลั๊กอินใหม่)</em>' );
	}
	$p = $poster ? ' poster="' . esc_url( $poster ) . '"' : '';
	return '<!-- wp:video {"className":"jw-video"} --><figure class="wp-block-video jw-video"><video controls preload="metadata" src="' . esc_url( $url ) . '"' . $p . '></video><figcaption class="wp-element-caption">ภาพบรรยากาศภายในร้าน และเสียงสัมภาษณ์เจ้าของร้านจิรวัสสังฆภัณฑ์ โคราช (2:50 นาที)</figcaption></figure><!-- /wp:video -->';
}
function jw_media_text( $img, $alt, $inner, $flip = false, $class = 'jw-split' ) {
	$a = $flip ? ',"mediaPosition":"right"' : '';
	$c = 'wp-block-media-text is-stacked-on-mobile ' . $class . ( $flip ? ' has-media-on-the-right' : '' );
	return '<!-- wp:media-text {"align":"wide","mediaType":"image","className":"' . $class . '","verticalAlignment":"center","imageFill":true' . $a . '} --><div class="' . $c . ' alignwide is-vertically-aligned-center is-image-fill-element"><figure class="wp-block-media-text__media"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( $alt ) . '"/></figure><div class="wp-block-media-text__content">' . $inner . '</div></div><!-- /wp:media-text -->';
}

function jirawat_setup_run() {
	kses_remove_filters(); // อนุญาต iframe/SVG ในเนื้อหาที่ปลั๊กอินสร้าง

	update_option( 'blogname', 'ร้านจิรวัสสังฆภัณฑ์ โคราช' );
	update_option( 'blogdescription', 'ครบเรื่องสังฆภัณฑ์ ชุดสังฆทาน เครื่องบวช เครื่องกฐิน ใจกลางเมืองโคราช' );
	update_option( 'timezone_string', 'Asia/Bangkok' );
	update_option( 'date_format', 'j F Y' );
	update_option( 'permalink_structure', '/%postname%/' );

	// ---------- รูปและวิดีโอ ----------
	$photos = array();
	foreach ( glob( JIRAWAT_DIR . 'assets/photo_*.jpg' ) ?: array() as $f ) {
		$photos[] = jirawat_import_asset( basename( $f ), 'ภาพบรรยากาศร้าน ' . basename( $f, '.jpg' ) );
	}
	$ph     = function ( $i ) use ( $photos ) { return $photos[ $i ] ?? ( $photos[0] ?? '' ); };
	$video  = jirawat_import_asset( 'promo.mp4', 'คลิปโปรโมทร้านจิรวัสสังฆภัณฑ์' );
	$poster = jirawat_import_asset( 'poster.jpg', 'ภาพปกคลิป' );
	$map    = 'https://maps.app.goo.gl/WwoapAAt14DzQ1vL6';
	$fb     = 'https://www.facebook.com/profile.php?id=100057257358453';
	$tel    = 'tel:0884797541';
	$hero   = $ph( 0 );

	$contact_list = '<ul class="jw-contact">' .
		'<li>' . jirawat_icon( 'pin' ) . '<span>943 ต.หัวทะเล อ.เมืองนครราชสีมา<br>จ.นครราชสีมา 30000</span></li>' .
		'<li>' . jirawat_icon( 'phone' ) . '<a href="' . $tel . '">088-479-7541</a></li>' .
		'<li>' . jirawat_icon( 'chat' ) . '<a href="' . esc_url( $fb ) . '" target="_blank" rel="noreferrer noopener">Facebook ร้านจิรวัสสังฆภัณฑ์</a></li>' .
		'<li>' . jirawat_icon( 'clock' ) . '<span>เวลาเปิด-ปิด โทรสอบถามที่ร้านได้เลย</span></li></ul>';

	// ================= หน้าแรก =================
	$home =
		// HERO
		'<!-- wp:cover {"url":"' . esc_url( $hero ) . '","dimRatio":0,"minHeight":92,"minHeightUnit":"vh","contentPosition":"bottom left","align":"full","className":"jw-hero"} --><div class="wp-block-cover alignfull has-custom-content-position is-position-bottom-left jw-hero" style="min-height:92vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="หน้าร้านจิรวัสสังฆภัณฑ์ โคราช" src="' . esc_url( $hero ) . '" data-object-fit="cover"/><div class="wp-block-cover__inner-container">' .
		jw_h( 'ครบทุกงานบุญ<br>ในร้านเดียว', 1, 'jw-hero-title' ) .
		jw_p( 'ชุดสังฆทาน ไตรจีวร เครื่องบวช เครื่องกฐิน และของบูชา คัดของที่พระใช้ได้จริง พร้อมคนช่วยจัดให้ตรงพิธีและงบของท่าน', 'jw-hero-lead' ) .
		jw_btns( jw_btn( 'ดูคลิปแนะนำร้าน', home_url( '/video/' ) ) . jw_btn( 'นำทางไปร้าน', $map, 'outline', true ) ) .
		'</div></div><!-- /wp:cover -->' .

		// INTRO STATEMENT
		jw_group(
			jw_p( 'ร้านจิรวัสสังฆภัณฑ์ โคราช', 'jw-signature' ) .
			jw_h( 'ร้านสังฆภัณฑ์ย่านหัวทะเล ที่ลูกค้าแวะก่อนไปวัด เพราะรู้ว่าจะได้ของครบ และได้คำแนะนำที่ถูกต้อง', 2, 'jw-statement' ),
			'jw-intro'
		) .

		// FEATURES (เส้นแบ่งแทนการ์ด)
		jw_group(
			jw_html( '<div class="jw-features alignwide">' .
				'<div class="jw-feature">' . jirawat_icon( 'lotus' ) . '<h3>สินค้าครบทุกหมวด</h3><p>ตั้งแต่ธูปเทียน ชุดสังฆทาน ไปจนถึงไตรจีวร อัฐบริขาร ชุดบวช และชุดกฐิน-ผ้าป่า</p></div>' .
				'<div class="jw-feature">' . jirawat_icon( 'hands' ) . '<h3>แนะนำด้วยใจ</h3><p>ไม่แน่ใจว่าต้องเตรียมอะไร ทางร้านช่วยจัดชุดให้ตรงกับพิธีและงบประมาณ</p></div>' .
				'<div class="jw-feature">' . jirawat_icon( 'route' ) . '<h3>แวะง่าย ใกล้เมือง</h3><p>อยู่ ต.หัวทะเล อ.เมืองนครราชสีมา ซื้อของแล้วไปวัดต่อได้ทันที</p></div>' .
			'</div>' ),
			'jw-band-features', 'wide'
		) .

		// CATEGORY SPLITS
		jw_media_text( $ph( 5 ), 'ชุดสังฆทานในถังเหลือง', jw_h( 'ชุดสังฆทาน จัดตามงบ', 2 ) . jw_p( 'ชุดเล็กสำหรับทำบุญทั่วไป จนถึงชุดใหญ่สำหรับงานพิธี ทุกชุดเลือกของที่พระสงฆ์นำไปใช้ได้จริง' ) . jw_btns( jw_btn( 'ดูสินค้าทั้งหมด', home_url( '/products/' ), 'outline' ) ) ) .
		jw_media_text( $ph( 8 ), 'พระพุทธรูปในตู้กระจก', jw_h( 'พระพุทธรูปและโต๊ะหมู่บูชา', 2 ) . jw_p( 'พระพุทธรูปหลายปาง โต๊ะหมู่ เชิงเทียน และเครื่องบูชา สำหรับบ้านและสำนักงาน จัดแสดงในตู้ให้เลือกชมได้ใกล้ ๆ' ) . jw_btns( jw_btn( 'ติดต่อสอบถาม', home_url( '/contact/' ), 'outline' ) ), true ) .

		// VIDEO BAND
		jw_group(
			jw_h( 'เดินชมร้าน ฟังเรื่องเล่าจากเจ้าของ', 2, 'jw-on-dark' ) .
			jw_p( 'คลิปสั้น 3 นาที พาชมบรรยากาศภายในร้าน และฟังเจ้าของร้านเล่าถึงสินค้าขายดีและลูกค้าที่ผูกพันกันมา', 'jw-on-dark-lead' ) .
			jw_video( $video, $poster ),
			'jw-band-dark'
		) .

		// GALLERY
		jw_group(
			jw_h( 'บรรยากาศภายในร้าน', 2 ) . jw_gallery( array( $ph( 3 ), $ph( 4 ), $ph( 6 ), $ph( 7 ), $ph( 9 ), $ph( 11 ) ) ),
			'jw-gallery-wrap', 'wide'
		) .

		// QUOTE
		jw_group(
			'<!-- wp:quote {"className":"jw-quote"} --><blockquote class="wp-block-quote jw-quote">' . jw_p( 'สังฆทานจัดดีมีคุณภาพ ของใช้ประโยชน์ได้จริง' ) . '<cite>รีวิวจากลูกค้าบน Google Maps · คะแนนเฉลี่ย 5.0</cite></blockquote><!-- /wp:quote -->',
			'jw-band-paper'
		) .

		// VISIT
		jw_group(
			'<!-- wp:columns {"align":"wide","className":"jw-visit"} --><div class="wp-block-columns alignwide jw-visit"><!-- wp:column --><div class="wp-block-column">' .
			jw_h( 'แวะมาที่ร้าน', 2 ) . jw_html( $contact_list ) . jw_btns( jw_btn( 'เปิดใน Google Maps', $map, 'fill', true ) ) .
			'</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">' .
			jw_html( '<iframe class="jw-map" title="แผนที่ร้านจิรวัสสังฆภัณฑ์" src="https://maps.google.com/maps?q=14.9503001,102.1373857&z=17&output=embed" loading="lazy"></iframe>' ) .
			'</div><!-- /wp:column --></div><!-- /wp:columns -->',
			'jw-visit-wrap', 'wide'
		);

	// ================= เกี่ยวกับร้าน =================
	$about =
		jw_media_text( $ph( 1 ), 'หน้าร้านจิรวัสสังฆภัณฑ์',
			jw_p( 'เรื่องของร้าน', 'jw-signature' ) .
			jw_h( 'ร้านสังฆภัณฑ์ที่ตั้งใจให้ทุกงานบุญสมบูรณ์', 2 ) .
			jw_p( '<strong>ร้านจิรวัสสังฆภัณฑ์ โคราช</strong> (Chirawat Monk Supply) จำหน่ายสินค้าสังฆภัณฑ์และเครื่องใช้ในงานบุญครบวงจร ที่ตำบลหัวทะเล อำเภอเมืองนครราชสีมา ให้บริการทั้งประชาชนทั่วไป หน่วยงาน ชุมชน และพระภิกษุสงฆ์' ) .
			jw_p( 'ทางร้านคัดสรรสินค้าที่มีคุณภาพ ใช้ประโยชน์ได้จริงสำหรับพระสงฆ์ และช่วยลูกค้าเตรียมของสำหรับพิธีต่าง ๆ ทั้งถวายสังฆทาน งานบวช งานกฐิน งานผ้าป่า หรือจัดโต๊ะหมู่บูชาที่บ้าน' )
		) .
		jw_group(
			jw_h( 'จากบทสัมภาษณ์เจ้าของร้าน', 2 ) .
			'<!-- wp:list {"className":"jw-facts"} --><ul class="wp-block-list jw-facts">' .
			'<li><strong>ร้านเปิดให้บริการมาแล้ว</strong><mark>[ใส่จากคลิปสัมภาษณ์]</mark></li>' .
			'<li><strong>สินค้าขายดีตลอดทั้งปี</strong><mark>[ใส่จากคลิปสัมภาษณ์]</mark></li>' .
			'<li><strong>สินค้าที่ถามหาช่วงเทศกาล</strong><mark>[ใส่จากคลิปสัมภาษณ์]</mark></li>' .
			'<li><strong>กลุ่มลูกค้าหลัก</strong><mark>[ใส่จากคลิปสัมภาษณ์]</mark></li>' .
			'<li><strong>ช่องทางออนไลน์</strong>Facebook ของร้าน</li>' .
			'</ul><!-- /wp:list -->',
			'jw-band-paper'
		) .
		jw_group( jw_gallery( array( $ph( 0 ), $ph( 2 ), $ph( 12 ) ) ), 'jw-gallery-wrap', 'wide' );

	// ================= สินค้า =================
	$cats = array(
		array( 'ชุดสังฆทานสำเร็จรูป', 'จัดชุดตามงบประมาณ คัดของที่พระใช้ได้จริง ทั้งชุดเล็ก ชุดใหญ่ และชุดถังสังฆทาน', 5 ),
		array( 'ไตรจีวรและอัฐบริขาร', 'ชุดผ้าไตรเต็ม ไตรจีวร สบง อังสะ บาตร และเครื่องอัฐบริขารครบชุด', 6 ),
		array( 'เครื่องบวชและเครื่องกฐิน', 'ชุดนาค ชุดบวช ชุดกฐิน ผ้าป่า และของใช้ประกอบพิธี จัดให้ครบตามรายการ', 3 ),
		array( 'พระพุทธรูปและโต๊ะหมู่บูชา', 'พระพุทธรูป โต๊ะหมู่ แจกัน เชิงเทียน กระถางธูป สำหรับบ้านและสำนักงาน', 8 ),
		array( 'ธูป เทียน และเทียนพรรษา', 'ธูปหอม เทียนขาว เทียนหอม และเทียนพรรษาสำหรับเทศกาลเข้าพรรษา', 10 ),
		array( 'ดอกไม้และของถวายอื่น ๆ', 'พวงมาลัย ดอกไม้ประดิษฐ์ ซองปัจจัย และของใช้เบ็ดเตล็ดในงานบุญ', 12 ),
	);
	$products = jw_group(
		jw_p( 'ต้องการจัดชุดพิเศษ หรือสั่งจำนวนมากสำหรับงานของหน่วยงาน โทรสั่งจองล่วงหน้าได้ที่ <a href="' . $tel . '">088-479-7541</a>', 'jw-lead' ),
		'jw-page-intro', 'wide'
	);
	foreach ( $cats as $i => $c ) {
		$products .= jw_media_text( $ph( $c[2] ), $c[0], jw_h( $c[0], 2 ) . jw_p( $c[1] ), (bool) ( $i % 2 ), 'jw-split jw-split-compact' );
	}
	$products .= jw_group( jw_h( 'ไม่แน่ใจว่าต้องใช้อะไร ให้ร้านช่วยจัด', 2, 'jw-on-dark' ) . jw_btns( jw_btn( 'โทร 088-479-7541', $tel ) . jw_btn( 'ทักทาง Facebook', $fb, 'outline', true ) ), 'jw-band-dark jw-cta' );

	// ================= วิดีโอ =================
	$video_page = jw_group(
			jw_p( 'ชมบรรยากาศภายในร้านจิรวัสสังฆภัณฑ์ โคราช และฟังเรื่องราวจากเจ้าของร้าน ถึงความตั้งใจในการคัดสรรสินค้าและการดูแลลูกค้าทุกคน', 'jw-lead' ) . jw_video( $video, $poster ),
			'jw-page-intro', 'wide'
		) .
		jw_group( jw_h( 'ภาพบรรยากาศเพิ่มเติม', 2 ) . jw_gallery( array( $ph( 9 ), $ph( 10 ), $ph( 11 ) ) ), 'jw-gallery-wrap', 'wide' );

	// ================= รายงาน =================
	$report = jw_group(
		jw_p( 'Dashboard ผลการสำรวจพฤติกรรมและความพึงพอใจของลูกค้าร้านจิรวัสสังฆภัณฑ์ โคราช (รายวิชา 1101033) แบ่งเป็น 4 ส่วน คือ ข้อมูลทั่วไป พฤติกรรมลูกค้า ความพึงพอใจ และข้อเสนอแนะ', 'jw-lead' ) .
		'<!-- wp:shortcode -->[jirawat_dashboard]<!-- /wp:shortcode -->',
		'jw-page-intro', 'wide'
	);

	// ================= ติดต่อ =================
	$contact = jw_group(
		'<!-- wp:columns {"align":"wide","className":"jw-visit"} --><div class="wp-block-columns alignwide jw-visit"><!-- wp:column --><div class="wp-block-column">' .
		jw_h( 'ร้านจิรวัสสังฆภัณฑ์ โคราช', 2 ) . jw_html( $contact_list ) .
		jw_btns( jw_btn( 'เปิดใน Google Maps', $map, 'fill', true ) . jw_btn( 'โทรหาร้าน', $tel, 'outline' ) ) .
		'</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">' .
		jw_html( '<iframe class="jw-map" title="แผนที่ร้านจิรวัสสังฆภัณฑ์" src="https://maps.google.com/maps?q=14.9503001,102.1373857&z=17&output=embed" loading="lazy"></iframe>' ) .
		'</div><!-- /wp:column --></div><!-- /wp:columns -->',
		'jw-page-intro', 'wide'
	);

	$ids = array(
		'home'     => jirawat_upsert_page( 'home', 'หน้าแรก', $home, 1 ),
		'about'    => jirawat_upsert_page( 'about', 'เกี่ยวกับร้าน', $about, 2 ),
		'products' => jirawat_upsert_page( 'products', 'สินค้าของเรา', $products, 3 ),
		'video'    => jirawat_upsert_page( 'video', 'วิดีโอแนะนำร้าน', $video_page, 4 ),
		'report'   => jirawat_upsert_page( 'report', 'รายงาน', $report, 5 ),
		'contact'  => jirawat_upsert_page( 'contact', 'ติดต่อเรา', $contact, 6 ),
	);

	update_post_meta( $ids['home'], '_wp_page_template', 'page-no-title' );
	foreach ( array( 'about', 'products', 'video', 'report', 'contact' ) as $k ) {
		delete_post_meta( $ids[ $k ], '_wp_page_template' );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );

	foreach ( array( 'sample-page', 'hello-world' ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) );
		if ( $p ) {
			wp_trash_post( $p->ID );
		}
	}

	// ---------- หัวเว็บ + ส่วนท้าย (template parts ของธีม) ----------
	$theme  = get_stylesheet();
	$header = '<!-- wp:group {"align":"full","className":"jw-header","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull jw-header"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} --><div class="wp-block-group alignwide">' .
		'<!-- wp:site-title {"level":0,"className":"jw-brand"} /-->' .
		'<!-- wp:navigation {"className":"jw-nav","overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"}} /-->' .
		'</div><!-- /wp:group --></div><!-- /wp:group -->';
	$footer = '<!-- wp:group {"align":"full","className":"jw-footer","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull jw-footer">' .
		'<!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column {"width":"55%"} --><div class="wp-block-column" style="flex-basis:55%">' .
		jw_p( 'ร้านจิรวัสสังฆภัณฑ์ โคราช', 'jw-footer-brand' ) . jw_p( 'ครบเรื่องสังฆภัณฑ์ ชุดสังฆทาน ไตรจีวร เครื่องบวช เครื่องกฐิน ใจกลางเมืองโคราช' ) .
		'</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">' . jw_html( $contact_list ) . '</div><!-- /wp:column --></div><!-- /wp:columns -->' .
		jw_p( 'เว็บไซต์นี้จัดทำเพื่อการศึกษา รายวิชา 1101023 และ 1101033 มหาวิทยาลัยเทคโนโลยีสุรนารี', 'jw-footer-note' ) .
		'</div><!-- /wp:group -->';
	foreach ( array( 'header' => $header, 'footer' => $footer ) as $slug => $content ) {
		$tp = get_posts( array(
			'post_type'   => 'wp_template_part',
			'name'        => $slug,
			'post_status' => 'any',
			'numberposts' => 1,
			'tax_query'   => array( array( 'taxonomy' => 'wp_theme', 'field' => 'name', 'terms' => $theme ) ),
		) );
		if ( $tp ) {
			wp_update_post( array( 'ID' => $tp[0]->ID, 'post_content' => $content ) );
		} else {
			$tid = wp_insert_post( array( 'post_type' => 'wp_template_part', 'post_name' => $slug, 'post_title' => ucfirst( $slug ), 'post_status' => 'publish', 'post_content' => $content ) );
			wp_set_object_terms( $tid, $theme, 'wp_theme' );
			wp_set_object_terms( $tid, $slug, 'wp_template_part_area' );
		}
	}

	// ---------- เมนู ----------
	$menu_name = 'เมนูหลัก';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	if ( ! is_wp_error( $menu_id ) ) {
		foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
			wp_delete_post( $item->ID, true );
		}
		$nav_links = '';
		foreach ( $ids as $key => $pid ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-object-id' => $pid,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			) );
			$cls        = 'report' === $key ? ',"className":"jw-nav-report"' : '';
			$nav_links .= '<!-- wp:navigation-link {"label":"' . esc_attr( get_the_title( $pid ) ) . '","type":"page","id":' . (int) $pid . ',"url":"' . esc_url( get_permalink( $pid ) ) . '","kind":"post-type"' . $cls . '} /-->';
		}
		$locs = get_theme_mod( 'nav_menu_locations', array() );
		foreach ( array_keys( get_registered_nav_menus() ) as $loc ) {
			$locs[ $loc ] = $menu_id;
		}
		set_theme_mod( 'nav_menu_locations', $locs );

		$nav      = get_posts( array( 'post_type' => 'wp_navigation', 'numberposts' => -1, 'post_status' => 'any' ) );
		$nav_data = array( 'post_type' => 'wp_navigation', 'post_title' => $menu_name, 'post_status' => 'publish', 'post_content' => $nav_links );
		if ( $nav ) {
			foreach ( $nav as $n ) {
				wp_update_post( array_merge( $nav_data, array( 'ID' => $n->ID, 'post_title' => $n->post_title ) ) );
			}
		} else {
			wp_insert_post( $nav_data );
		}
	}

	flush_rewrite_rules();
}
