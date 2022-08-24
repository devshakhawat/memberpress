<?php if ( ! defined( 'ABSPATH' ) ) {die( 'You are not allowed to call this page directly.' );} ?>

<?php
MeprUpdateCtrl::manually_queue_update();
$license                         = get_site_transient('mepr_license_info');
$current_license                 = $license && isset($license['product_slug']) ? $license['product_slug'] : '';
$features_list                   = MeprOnboardingHelper::features_list();
$features_data                   = MeprOnboardingHelper::get_selected_features_data( get_current_user_id() );
$addons_not_installed            = isset( $features_data['addons_not_installed'] ) ? $features_data['addons_not_installed'] : array();
$addons_installed                = isset( $features_data['addons_installed'] ) ? $features_data['addons_installed'] : array();
$addons_upgrade_failed           = isset( $features_data['addons_upgrade_failed'] ) ? $features_data['addons_upgrade_failed'] : array();
$mepr_onboarding_payment_gateway = get_option( 'mepr_onboarding_payment_gateway' );

$upgrade_type = MeprOnboardingHelper::is_upgrade_required(
  array(
    'addons_installed'     => $addons_installed,
    'addons_not_installed' => $addons_not_installed,
    'payment_gateway'      => $mepr_onboarding_payment_gateway,
  )
);

if ( false !== $upgrade_type || $mepr_onboarding_payment_gateway == 'MeprAuthorizeGateway') {

  $cta_data    = MeprOnboardingHelper::get_upgrade_cta_data( $upgrade_type );
  $pricing_url = $cta_data['url'];

  $finish_description = $cta_data['heading'];
  if ( in_array( 'easy-affiliate', $features_data['addons_not_installed'], true ) ) {
    $finish_description = sprintf( esc_html__( 'To unlock selected features, upgrade to %s with Easy Affiliate.', 'memberpress' ), $cta_data['token']);
    $pricing_url        = add_query_arg(
      array(
        'onboarding' => 1,
        'doea'       => 1,
        'return_url' => urlencode( admin_url( 'admin.php?page=memberpress-onboarding&step=7&onboarding=1' ) ),
      ),
      $pricing_url
    );
  } else {
    $pricing_url = add_query_arg(
      array(
        'onboarding' => 1,
        'return_url' => urlencode( admin_url( 'admin.php?page=memberpress-onboarding&step=7&onboarding=1' ) ),
      ),
      $pricing_url
    );
  }

  if ( ! empty( $addons_installed ) ) {
    $finish_description = '';
    $pricing_url       = '';
  }

  $target = '';
  if( 1 == count($features_data['addons_not_installed'])
    && in_array( 'easy-affiliate', $features_data['addons_not_installed'], true )
    && in_array( $current_license, ['memberpress-pro-5','memberpress-pro'], true )
  ){
    $finish_description = '';
    $pricing_url       = 'https://easyaffiliate.com/ipob/pricing/';
    $cta_data['label'] = esc_html__( 'Purchase Easy Affiliate', 'memberpress' );
    $target = ' target="_blank"';
  }

  // if license mismatched and upgrade still required, show them the upgrade CTA interface.
  if ( $pricing_url != '' && $current_license != $upgrade_type ) : ?>
    <h2 class="mepr-wizard-step-title"><?php esc_html_e( 'Finish setup', 'memberpress' ); ?></h2>
    <p class="mepr-wizard-step-description"><?php echo esc_html( $finish_description ); ?></p>

    <div class="mepr-wizard-features">
      <?php
      foreach ( $addons_not_installed as $i => $addon_slug ) :
        $mepr_active_class = 'mepr-wizard-feature-activatedx';
        if ( in_array( $addon_slug, $addons_installed, true ) ) {
          $mepr_active_class = 'mepr-wizard-feature-activated';
        }

        $addons_installation_message       = '';
        $addons_installation_message_class = '';
        if ( in_array( $addon_slug, $addons_upgrade_failed, true ) ) {
          $addons_installation_message       = esc_html__( 'Unable to install. Please download and install manually.', 'memberpress' );
          $addons_installation_message_class = 'error';
        }
        ?>
        <div class="mepr-wizard-feature no-border no-padding">
          <div class="<?php echo esc_attr( $mepr_active_class ); ?>">
            <h3><span class="step-complete"></span> <?php echo esc_html( $features_list[ $addon_slug ] ); ?></h3>
            <p class="<?php echo esc_attr( $addons_installation_message_class ); ?>"><?php echo esc_html( $addons_installation_message ); ?></p>
          </div>
          <div class="mepr-wizard-feature-right"></div>
        </div>
      <?php endforeach; ?>

      <?php if ( $mepr_onboarding_payment_gateway == 'MeprAuthorizeGateway' ) : ?>
        <div class="mepr-wizard-feature no-border no-padding MeprAuthorizeGateway" data-slug="MeprAuthorizeGateway">
          <div class="mepr-wizard-feature-activatedx">
            <h3><span class="step-complete"></span> <?php esc_html_e( 'Authorize.net', 'memberpress' ); ?></h3>
            <p></p>
          </div>
          <div class="mepr-wizard-feature-right"></div>
        </div>
      <?php endif; ?>

    </div>

    <div class="mepr-wizard-button-group">
      <a <?php echo $target; ?> href="<?php echo $pricing_url; ?>" id="mepr-wizard-create-new-content" class="mepr-wizard-button-orange"><?php echo esc_html( $cta_data['label'] ); ?></a>
    </div>

  <?php
  // lets run the upgrade.
  else :
    echo '<input type="hidden" id="mepr_wizard_finalize_setup" value="1" />';
    $editions = MeprUtils::is_incorrect_edition_installed();
    if ( $editions ) {
      echo '<input type="hidden" id="mepr_wizard_install_correct_edition" value="1" />';
    }
    ?>
    <h2 class="mepr-wizard-step-title"><?php esc_html_e( 'Finishing setup', 'memberpress' ); ?></h2>
    <p class="mepr-wizard-step-description"><?php echo esc_html__( "Please don't close the browser.", 'memberpress' ); ?> <i class="mp-icon mp-icon-spinner animate-spin mepr-wizard-finish-step-processing"></i></p>
    <div class="mepr-wizard-features">
      <?php foreach ( $features_data['addons_not_installed'] as $i => $addon_slug ) : ?>
        <div class="mepr-wizard-feature no-border no-padding" id="mepr-finish-step-addon-<?php echo esc_attr( $addon_slug ); ?>">
          <div class="mepr-wizard-feature-activatedx">
            <h3><span class="step-complete"></span> <?php echo esc_html( $features_list[ $addon_slug ] ); ?></h3>
            <p class="mepr-wizard-addon-text"></p>
          </div>
          <div class="mepr-wizard-feature-right"><i id="mepr-wizard-finish-step-<?php echo esc_attr( $addon_slug ); ?>" class="mp-icon mp-icon-spinner animate-spin"></i></div>
        </div>
        <?php if ( $i == 0 ) : ?>
          <input type="hidden" id="start_addon_slug_installable" value="<?php echo esc_attr( $addon_slug ); ?>" />
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ( $mepr_onboarding_payment_gateway == 'MeprAuthorizeGateway' ) : ?>
        <input type="hidden" id="mepr-wizard-finish-add-authorize-gateway" value="1" />
        <div class="mepr-wizard-feature no-border no-padding" id="mepr-wizard-finish-step-addon-MeprAuthorizeGateway" data-slug="MeprAuthorizeGateway">
          <div class="mepr-wizard-feature-activatedx">
            <h3><span class="step-complete"></span> <?php esc_html_e( 'Authorize.net', 'memberpress' ); ?></h3>
            <p class="mepr-wizard-addon-text">
              <button type="button" id="mepr-wizard-finish-configure-authorize" class="mepr-wizard-button-secondary"><?php esc_html_e( 'Configure Gateway', 'memberpress' ); ?></button>
            </p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php
} else {
  ?>
  <h2 class="mepr-wizard-step-title"><?php esc_html_e( 'Finishing setup', 'memberpress' ); ?></h2>
  <p class="mepr-wizard-step-description">
    <?php esc_html_e( 'Please wait..', 'memberpress' ); ?>
    <i class="mp-icon mp-icon-spinner animate-spin"></i>
  </p>
  <input type="hidden" id="mepr-finishing-setup-redirect" value="1" />
<?php } ?>
