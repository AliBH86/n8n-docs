<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ah-admin-wrap">
    <h1>String Translations — <span style="font-family:Tajawal,Arial;direction:rtl">الترجمات</span></h1>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p>Translation saved successfully.</p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['deleted'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p>Translation deleted.</p></div>
    <?php endif; ?>

    <div class="ah-context-tabs">
        <?php $contexts = [ 'woocommerce' => 'WooCommerce', 'ui' => 'UI / General', 'custom' => 'Custom' ];
        foreach ( $contexts as $key => $label ) : ?>
        <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-strings&context=' . $key ); ?>"
           class="ah-tab<?php echo $context === $key ? ' active' : ''; ?>">
            <?php echo esc_html( $label ); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Add new string -->
    <div class="ah-add-string-form">
        <h3>Add New Translation</h3>
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'ah_string_save' ); ?>
            <input type="hidden" name="action" value="ah_save_string" />
            <input type="hidden" name="context" value="<?php echo esc_attr( $context ); ?>" />
            <div class="ah-form-row">
                <div>
                    <label>English Source Text</label>
                    <input type="text" name="source_key" placeholder="e.g. Add to cart" required style="width:100%" />
                </div>
                <div>
                    <label>Arabic Translation — الترجمة</label>
                    <input type="text" name="translation" placeholder="e.g. أضف إلى السلة" required
                           style="width:100%;direction:rtl;font-family:Tajawal,Arial,sans-serif;font-size:15px;" />
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" class="button button-primary">Add Translation</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Existing strings table -->
    <table class="widefat ah-strings-table">
        <thead>
            <tr>
                <th>English Source</th>
                <th>Arabic Translation — الترجمة</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $strings ) ) : ?>
            <tr><td colspan="4" style="text-align:center;padding:20px;color:#888;">No strings yet. Add translations above.</td></tr>
        <?php else : ?>
            <?php foreach ( $strings as $row ) : ?>
            <tr class="ah-string-row" data-id="<?php echo esc_attr( $row['id'] ); ?>">
                <td class="ah-source-col">
                    <code><?php echo esc_html( $row['source_key'] ); ?></code>
                </td>
                <td class="ah-trans-col">
                    <span class="ah-trans-text" dir="rtl" style="font-family:Tajawal,Arial,sans-serif;font-size:15px;">
                        <?php echo esc_html( $row['translation'] ); ?>
                    </span>
                    <input type="text" class="ah-trans-input" value="<?php echo esc_attr( $row['translation'] ); ?>"
                           dir="rtl" style="display:none;font-family:Tajawal,Arial,sans-serif;font-size:15px;width:100%;" />
                </td>
                <td style="white-space:nowrap;color:#888;font-size:12px;"><?php echo esc_html( $row['updated_at'] ); ?></td>
                <td class="ah-actions-col">
                    <button class="button button-small ah-edit-btn">Edit</button>
                    <button class="button button-small ah-save-btn" style="display:none;color:#16a34a;">Save</button>
                    <button class="button button-small ah-cancel-btn" style="display:none;">Cancel</button>
                    <button class="button button-small ah-delete-btn" style="color:#dc2626;" data-id="<?php echo esc_attr( $row['id'] ); ?>">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total > 50 ) : ?>
    <div class="ah-pagination">
        <?php for ( $i = 1; $i <= ceil( $total / 50 ); $i++ ) : ?>
        <a href="<?php echo admin_url( 'admin.php?page=ah-arabic-strings&context=' . $context . '&paged=' . $i ); ?>"
           class="<?php echo $paged === $i ? 'current' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
