<?php
/**
 * فیلدهای اختصاصی نشست: ویدئوی کوتاه، اطلاعات برگزاری و فایل‌های نشست.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * متاباکس، sanitize/ذخیره و داده نمایشی نشست.
 *
 * Repeater فایل‌ها از BA_Admin_Repeater_Component استفاده می‌کند (ADR-005)؛
 * sanitize و persistence در همین کلاس است.
 */
final class BA_Studies_Session_Meta {

	const META_KEY     = '_ba_session';
	const FIELD_NAME   = 'ba_session';
	const NONCE_ACTION = 'ba_session_meta_save';
	const NONCE_NAME   = 'ba_session_meta_nonce';
	const ASSET_HANDLE = 'ba-studies-session-admin';

	/**
	 * هوک‌های متاباکس را ثبت می‌کند.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'add_meta_boxes_' . BA_Studies_Content_Types::SESSION_POST_TYPE, array( __CLASS__, 'register_metabox' ) );
		add_action( 'save_post_' . BA_Studies_Content_Types::SESSION_POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * مقادیر پیش‌فرض متای نشست.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'video_id'       => 0,
			'video_url'      => '',
			'video_duration' => '',
			'lead'           => '',
			'event_type'     => '',
			'event_date'     => '',
			'time_start'     => '',
			'time_end'       => '',
			'speakers'       => '',
			'location'       => '',
			'files'          => array(),
		);
	}

	/**
	 * متای خام ذخیره‌شده یک نشست.
	 *
	 * @param int $post_id شناسه نشست.
	 * @return array
	 */
	public static function get( $post_id ) {
		$meta = get_post_meta( (int) $post_id, self::META_KEY, true );

		return wp_parse_args( is_array( $meta ) ? $meta : array(), self::get_defaults() );
	}

	/**
	 * متاباکس را روی صفحه ویرایش نشست ثبت می‌کند.
	 *
	 * @return void
	 */
	public static function register_metabox() {
		add_meta_box(
			'ba-session-details',
			'اطلاعات نشست، ویدئو و فایل‌ها',
			array( __CLASS__, 'render' ),
			BA_Studies_Content_Types::SESSION_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Assetهای متاباکس فقط روی صفحه ویرایش نشست.
	 *
	 * @param string $hook_suffix صفحه جاری.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || BA_Studies_Content_Types::SESSION_POST_TYPE !== $screen->post_type ) {
			return;
		}

		BA_Admin_Repeater_Component::enqueue_assets();
		wp_enqueue_media();

		$base = trailingslashit( get_stylesheet_directory() );
		$uri  = trailingslashit( get_stylesheet_directory_uri() );

		wp_enqueue_style(
			self::ASSET_HANDLE,
			$uri . 'assets/css/ba-studies-session-admin.css',
			array( BA_Admin_Repeater_Component::STYLE_HANDLE ),
			(string) filemtime( $base . 'assets/css/ba-studies-session-admin.css' )
		);
		wp_enqueue_script(
			self::ASSET_HANDLE,
			$uri . 'assets/js/ba-studies-session-admin.js',
			array( 'jquery', BA_Admin_Repeater_Component::SCRIPT_HANDLE ),
			(string) filemtime( $base . 'assets/js/ba-studies-session-admin.js' ),
			true
		);
		wp_localize_script(
			self::ASSET_HANDLE,
			'BAStudiesSessionAdmin',
			array(
				'videoTitle'  => 'انتخاب ویدئوی کوتاه نشست',
				'fileTitle'   => 'انتخاب فایل نشست',
				'useButton'   => 'استفاده از این فایل',
			)
		);
	}

	/**
	 * فرم متاباکس.
	 *
	 * @param WP_Post $post نشست جاری.
	 * @return void
	 */
	public static function render( $post ) {
		$meta = self::get( $post->ID );
		$name = self::FIELD_NAME;

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="ba-session-meta">
			<p class="ba-session-meta__hint">تصویر شاخص نشست، پوستر ویدئو و تصویر کارت است. «چکیده» خلاصه دوخطی کارت و متن اصلی، گزارش کوتاه نشست است.</p>

			<h3 class="ba-session-meta__title">ویدئوی کوتاه نشست</h3>
			<div class="ba-session-meta__grid">
				<?php self::render_media_field( $name . '[video_id]', (int) $meta['video_id'], 'فایل ویدئو (MP4)', 'video' ); ?>
				<?php self::render_text_field( $name . '[video_url]', $meta['video_url'], 'یا آدرس مستقیم فایل ویدئو', 'url', 'https://' ); ?>
				<?php self::render_text_field( $name . '[video_duration]', $meta['video_duration'], 'مدت کلیپ (اختیاری، مثلاً ۲:۴۰)', 'text', 'خالی = از متادیتای فایل' ); ?>
			</div>

			<h3 class="ba-session-meta__title">لید و اطلاعات برگزاری</h3>
			<p class="ba-session-meta__field">
				<label for="ba-session-lead">لید (خلاصه یک‌جمله‌ای پررنگ بالای گزارش)</label>
				<textarea id="ba-session-lead" class="widefat" rows="2" name="<?php echo esc_attr( $name . '[lead]' ); ?>"><?php echo esc_textarea( $meta['lead'] ); ?></textarea>
			</p>
			<div class="ba-session-meta__grid">
				<?php self::render_text_field( $name . '[event_type]', $meta['event_type'], 'نوع رویداد', 'text', 'نشست تخصصی' ); ?>
				<?php self::render_text_field( $name . '[event_date]', $meta['event_date'], 'تاریخ برگزاری (میلادی)', 'date' ); ?>
				<?php self::render_text_field( $name . '[time_start]', $meta['time_start'], 'ساعت شروع', 'time' ); ?>
				<?php self::render_text_field( $name . '[time_end]', $meta['time_end'], 'ساعت پایان', 'time' ); ?>
			</div>
			<p class="ba-session-meta__field">
				<label for="ba-session-speakers">سخنرانان</label>
				<textarea id="ba-session-speakers" class="widefat" rows="2" name="<?php echo esc_attr( $name . '[speakers]' ); ?>"><?php echo esc_textarea( $meta['speakers'] ); ?></textarea>
			</p>
			<p class="ba-session-meta__field">
				<label for="ba-session-location">مکان</label>
				<input id="ba-session-location" class="widefat" type="text" name="<?php echo esc_attr( $name . '[location]' ); ?>" value="<?php echo esc_attr( $meta['location'] ); ?>" />
			</p>
			<p class="ba-session-meta__hint">حوزه نشست از جعبه «حوزه‌ها» انتخاب می‌شود.</p>

			<h3 class="ba-session-meta__title">فایل‌های نشست</h3>
			<?php
			BA_Admin_Repeater_Component::render(
				array(
					'id'           => 'ba-session-files',
					'items'        => $meta['files'],
					'item_label'   => 'فایل',
					'add_label'    => 'افزودن فایل',
					'empty_label'  => 'هنوز فایلی اضافه نشده است.',
					'row_renderer' => array( __CLASS__, 'render_file_row' ),
					'context'      => $name,
					'class_name'   => 'ba-session-files',
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * فیلدهای یک ردیف فایل.
	 *
	 * @param string|int $index   اندیس.
	 * @param array      $item    داده ردیف.
	 * @param mixed      $context نام پایه.
	 * @return void
	 */
	public static function render_file_row( $index, array $item, $context ) {
		$item = wp_parse_args( $item, array( 'title' => '', 'file_id' => 0, 'meta' => '' ) );
		$base = (string) $context;
		?>
		<div class="ba-session-meta__grid">
			<?php self::render_text_field( BA_Admin_Repeater_Component::field_name( $base, 'files', $index, 'title' ), $item['title'], 'عنوان فایل', 'text', 'ویدئوی کامل نشست' ); ?>
			<?php self::render_media_field( BA_Admin_Repeater_Component::field_name( $base, 'files', $index, 'file_id' ), (int) $item['file_id'], 'فایل', '' ); ?>
			<?php self::render_text_field( BA_Admin_Repeater_Component::field_name( $base, 'files', $index, 'meta' ), $item['meta'], 'متا (اختیاری)', 'text', '۱۴ صفحه / ۱ ساعت و ۵۲ دقیقه' ); ?>
		</div>
		<?php
	}

	/**
	 * فیلد متنی ساده.
	 *
	 * @param string $name        نام ورودی.
	 * @param string $value       مقدار.
	 * @param string $label       برچسب.
	 * @param string $type        نوع input.
	 * @param string $placeholder placeholder.
	 * @return void
	 */
	private static function render_text_field( $name, $value, $label, $type = 'text', $placeholder = '' ) {
		$id = 'ba-session-' . md5( $name );
		?>
		<p class="ba-session-meta__field">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input id="<?php echo esc_attr( $id ); ?>" class="widefat" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
		</p>
		<?php
	}

	/**
	 * انتخابگر فایل از Media Library.
	 *
	 * @param string $name  نام ورودی.
	 * @param int    $id    شناسه attachment.
	 * @param string $label برچسب.
	 * @param string $mime  محدودیت نوع رسانه در انتخابگر.
	 * @return void
	 */
	private static function render_media_field( $name, $id, $label, $mime ) {
		$file = $id ? get_attached_file( $id ) : '';
		?>
		<div class="ba-session-meta__field ba-session-media" data-ba-session-media data-ba-session-media-type="<?php echo esc_attr( $mime ); ?>">
			<span class="ba-session-media__label"><?php echo esc_html( $label ); ?></span>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $id ? (string) $id : '' ); ?>" data-ba-session-media-input />
			<span class="ba-session-media__name" data-ba-session-media-name><?php echo esc_html( $file ? wp_basename( $file ) : '' ); ?></span>
			<button type="button" class="button" data-ba-session-media-select>انتخاب فایل</button>
			<button type="button" class="button-link-delete" data-ba-session-media-remove <?php echo $id ? '' : 'hidden'; ?>>حذف</button>
		</div>
		<?php
	}

	/**
	 * ذخیره متای نشست.
	 *
	 * @param int     $post_id شناسه.
	 * @param WP_Post $post    نشست.
	 * @return void
	 */
	public static function save( $post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST[ self::FIELD_NAME ] ) && is_array( $_POST[ self::FIELD_NAME ] )
			? wp_unslash( $_POST[ self::FIELD_NAME ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize() پایین.
			: array();

		update_post_meta( $post_id, self::META_KEY, self::sanitize( $raw ) );
	}

	/**
	 * sanitize کامل داده متاباکس.
	 *
	 * @param array $raw ورودی خام.
	 * @return array
	 */
	public static function sanitize( array $raw ) {
		$clean = self::get_defaults();

		$clean['video_id']       = isset( $raw['video_id'] ) ? absint( $raw['video_id'] ) : 0;
		$clean['video_url']      = isset( $raw['video_url'] ) ? esc_url_raw( trim( (string) $raw['video_url'] ) ) : '';
		$clean['video_duration'] = isset( $raw['video_duration'] ) ? sanitize_text_field( (string) $raw['video_duration'] ) : '';
		$clean['lead']           = isset( $raw['lead'] ) ? sanitize_textarea_field( (string) $raw['lead'] ) : '';
		$clean['event_type']     = isset( $raw['event_type'] ) ? sanitize_text_field( (string) $raw['event_type'] ) : '';
		$clean['speakers']       = isset( $raw['speakers'] ) ? sanitize_textarea_field( (string) $raw['speakers'] ) : '';
		$clean['location']       = isset( $raw['location'] ) ? sanitize_text_field( (string) $raw['location'] ) : '';

		$date = isset( $raw['event_date'] ) ? BA_Studies_Center_Settings_Service::normalize_digits( trim( (string) $raw['event_date'] ) ) : '';
		$clean['event_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';

		foreach ( array( 'time_start', 'time_end' ) as $key ) {
			$time          = isset( $raw[ $key ] ) ? BA_Studies_Center_Settings_Service::normalize_digits( trim( (string) $raw[ $key ] ) ) : '';
			$clean[ $key ] = preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $time ) ? $time : '';
		}

		$files = isset( $raw['files'] ) && is_array( $raw['files'] ) ? $raw['files'] : array();

		foreach ( $files as $file ) {
			if ( ! is_array( $file ) ) {
				continue;
			}

			$row = array(
				'title'   => isset( $file['title'] ) ? sanitize_text_field( (string) $file['title'] ) : '',
				'file_id' => isset( $file['file_id'] ) ? absint( $file['file_id'] ) : 0,
				'meta'    => isset( $file['meta'] ) ? sanitize_text_field( (string) $file['meta'] ) : '',
			);

			// ردیف بدون فایل قابل دانلود نیست و ذخیره نمی‌شود.
			if ( ! $row['file_id'] ) {
				continue;
			}

			$clean['files'][] = $row;
		}

		return $clean;
	}

	/**
	 * داده آماده نمایش نشست برای ویجت‌های صفحه داخلی.
	 *
	 * @param int    $post_id     شناسه نشست.
	 * @param string $date_format فرمت تاریخ.
	 * @return array
	 */
	public static function get_view_data( $post_id, $date_format = 'j F Y' ) {
		$post_id = (int) $post_id;
		$meta    = self::get( $post_id );
		$video   = $meta['video_id'] ? wp_get_attachment_url( $meta['video_id'] ) : '';

		if ( ! $video && $meta['video_url'] ) {
			$video = $meta['video_url'];
		}

		$duration = $meta['video_duration'];

		if ( '' === $duration && $meta['video_id'] ) {
			$video_meta = wp_get_attachment_metadata( $meta['video_id'] );
			$duration   = is_array( $video_meta ) && ! empty( $video_meta['length_formatted'] ) ? $video_meta['length_formatted'] : '';
		}

		$event_date = '';

		if ( $meta['event_date'] ) {
			$timestamp  = strtotime( $meta['event_date'] . ' 12:00:00' );
			$event_date = $timestamp ? date_i18n( $date_format, $timestamp ) : '';
		}

		$areas = get_the_terms( $post_id, BA_Studies_Content_Types::AREA_TAXONOMY );
		$areas = is_array( $areas ) ? wp_list_pluck( $areas, 'name' ) : array();

		$files = array();

		foreach ( $meta['files'] as $file ) {
			$url = wp_get_attachment_url( $file['file_id'] );

			if ( ! $url ) {
				continue;
			}

			$files[] = self::describe_file( $file, $url );
		}

		return array(
			'title'      => get_the_title( $post_id ),
			'poster'     => get_the_post_thumbnail_url( $post_id, 'large' ),
			'video'      => $video,
			'duration'   => BA_Studies_Formatter::fa_digits( $duration ),
			'lead'       => $meta['lead'],
			'event_type' => $meta['event_type'],
			'event_date' => BA_Studies_Formatter::fa_digits( $event_date ),
			'time_start' => BA_Studies_Formatter::fa_digits( $meta['time_start'] ),
			'time_end'   => BA_Studies_Formatter::fa_digits( $meta['time_end'] ),
			'speakers'   => $meta['speakers'],
			'areas'      => $areas,
			'location'   => $meta['location'],
			'files'      => $files,
		);
	}

	/**
	 * عنوان، نوع و متای قابل نمایش یک فایل را می‌سازد.
	 *
	 * @param array  $file ردیف ذخیره‌شده.
	 * @param string $url  آدرس فایل.
	 * @return array
	 */
	private static function describe_file( array $file, $url ) {
		$path      = get_attached_file( $file['file_id'] );
		$extension = strtoupper( (string) pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$mime      = (string) get_post_mime_type( $file['file_id'] );
		$detail    = $file['meta'];

		if ( '' === $detail ) {
			$file_meta = wp_get_attachment_metadata( $file['file_id'] );

			if ( is_array( $file_meta ) && ! empty( $file_meta['length_formatted'] ) ) {
				$detail = $file_meta['length_formatted'];
			} elseif ( $path && file_exists( $path ) ) {
				$detail = size_format( (int) filesize( $path ), 1 );
			}
		}

		$title = '' !== $file['title'] ? $file['title'] : get_the_title( $file['file_id'] );

		return array(
			'title' => $title,
			'url'   => $url,
			'kind'  => BA_Studies_Formatter::file_kind( $mime, $extension ),
			'meta'  => BA_Studies_Formatter::fa_digits( implode( ' · ', array_filter( array( $detail, $extension ) ) ) ),
		);
	}
}
