<?php

/**
 * Plugin Name: Fisibul Internship Application Form
 * Plugin URI: https://yourwebsite.com
 * Description: A comprehensive internship application form with backend management and Excel export functionality.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FISIBUL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FISIBUL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FISIBUL_VERSION', '1.0.0');
define('FISIBUL_DB_VERSION', '1.1'); // Increment this when making DB changes

class FisibulInternshipPlugin
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }


    // Add this method to the FisibulInternshipPlugin class
    public function check_database_version()
    {
        $installed_version = get_option('fisibul_db_version', '1.0');

        if (version_compare($installed_version, FISIBUL_DB_VERSION, '<')) {
            $this->upgrade_database($installed_version);
        }
    }

    // Add this method to handle database upgrades
    private function upgrade_database($from_version)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fisibul_applications';

        // Upgrade from version 1.0 to 1.1 - Add 'level' column
        if (version_compare($from_version, '1.1', '<')) {
            // Check if column doesn't exist
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW COLUMNS FROM $table_name LIKE %s",
                'level'
            ));

            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN level varchar(100) AFTER course_study");
            }

            // Update version
            update_option('fisibul_db_version', '1.1');
        }

        // Add more version upgrades here as needed
        // Example for future version 1.2:
        // if (version_compare($from_version, '1.2', '<')) {
        //     // Add new column or modify structure
        //     update_option('fisibul_db_version', '1.2');
        // }
    }

    public function init()
    {
        // Create database table
        $this->create_table();
        $this->check_database_version();

        // Add hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_submit_internship_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_internship_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_export_applications', array($this, 'export_to_excel'));
        add_shortcode('fisibul_internship_form', array($this, 'display_form_shortcode'));
    }

    public function activate()
    {
        $this->create_table();
        
        // Set initial database version
        if (!get_option('fisibul_db_version')) {
            add_option('fisibul_db_version', FISIBUL_DB_VERSION);
        }

        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    private function create_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'fisibul_applications';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            middle_name varchar(100),
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            date_of_birth date NOT NULL,
            address text NOT NULL,
            education_level varchar(100) NOT NULL,
            education_other varchar(255),
            institution varchar(255) NOT NULL,
            course_study varchar(255) NOT NULL,
            passion_content varchar(10) NOT NULL,
            interest_reason text NOT NULL,
            career_goals text NOT NULL,
            preferred_track varchar(50) NOT NULL,
            experience_level varchar(50) NOT NULL,
            digital_tools text,
            access_computer varchar(10) NOT NULL,
            access_internet varchar(10) NOT NULL,
            availability varchar(20) NOT NULL,
            has_portfolio varchar(10),
            portfolio_link text,
            previous_experience text,
            heard_about varchar(100) NOT NULL,
            heard_other varchar(255),
            declaration_agreed varchar(10) NOT NULL,
            full_name_signature varchar(255) NOT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_frontend_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('fisibul-frontend', FISIBUL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), FISIBUL_VERSION, true);
        wp_enqueue_style('fisibul-frontend', FISIBUL_PLUGIN_URL . 'assets/frontend.css', array(), FISIBUL_VERSION);

        // Localize script for AJAX
        wp_localize_script('fisibul-frontend', 'fisibul_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fisibul_nonce')
        ));
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'fisibul') !== false) {
            wp_enqueue_script('fisibul-admin', FISIBUL_PLUGIN_URL . 'assets/admin.js', array('jquery'), FISIBUL_VERSION, true);
            wp_enqueue_style('fisibul-admin', FISIBUL_PLUGIN_URL . 'assets/admin.css', array(), FISIBUL_VERSION);

            wp_localize_script('fisibul-admin', 'fisibul_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fisibul_admin_nonce')
            ));
        }
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Fisibul Internships',
            'Internship Apps',
            'manage_options',
            'fisibul-internships',
            array($this, 'admin_page_applications'),
            'dashicons-id-alt',
            30
        );

        add_submenu_page(
            'fisibul-internships',
            'All Applications',
            'All Applications',
            'manage_options',
            'fisibul-internships',
            array($this, 'admin_page_applications')
        );

        add_submenu_page(
            'fisibul-internships',
            'Export Data',
            'Export Data',
            'manage_options',
            'fisibul-export',
            array($this, 'admin_page_export')
        );
    }

    public function display_form_shortcode($atts)
    {
        ob_start();
        $this->render_form();
        return ob_get_clean();
    }

    private function render_form()
    {
?>
        <div class="fisibul-form-container">
            <form id="fisibul-internship-form" method="post">
                <?php wp_nonce_field('fisibul_form_submit', 'fisibul_nonce'); ?>
                <!-- Section 1: Personal Information -->
                <div class="form-section">
                    <h3>Section 1: Personal Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number (WhatsApp preferred) *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth *</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Residential Address (City & State) *</label>
                        <textarea id="address" name="address" rows="3" required></textarea>
                    </div>
                </div>

                <!-- Section 2: Educational Background -->
                <div class="form-section">
                    <h3>Section 2: Educational Background</h3>

                    <div class="form-group">
                        <label for="education_level">Highest Level of Education Completed *</label>
                        <select id="education_level" name="education_level" required>
                            <option value="">Select Education Level</option>
                            <option value="secondary">Secondary School Certificate</option>
                            <option value="diploma">Diploma / OND</option>
                            <option value="undergraduate">Undergraduate (currently enrolled)</option>
                            <option value="graduate">Graduate (BSc, HND, etc.)</option>
                            <option value="postgraduate">Postgraduate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group" id="education_other_group" style="display: none;">
                        <label for="education_other">Please specify other education level</label>
                        <input type="text" id="education_other" name="education_other">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="institution">Institution (Name of school/university) *</label>
                            <input type="text" id="institution" name="institution" required>
                        </div>
                        <div class="form-group">
                            <label for="course_study">Course of Study / Discipline *</label>
                            <input type="text" id="course_study" name="course_study" required>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Eligibility & Motivation -->
                <div class="form-section">
                    <h3>Section 3: Eligibility & Motivation</h3>

                    <div class="form-group">
                        <label>Do you have a passion for content creation and digital skills development? *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="passion_content" value="yes" required> Yes</label>
                            <label><input type="radio" name="passion_content" value="no" required> No</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="interest_reason">Why are you interested in this internship program? *</label>
                        <textarea id="interest_reason" name="interest_reason" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="career_goals">What are your career goals, and how do you believe this program will help you achieve them? *</label>
                        <textarea id="career_goals" name="career_goals" rows="4" required></textarea>
                    </div>
                </div>

                <!-- Section 4: Track Selection & Skills -->
                <div class="form-section">
                    <h3>Section 4: Track Selection & Skills</h3>

                    <div class="form-group">
                        <label for="preferred_track">Most preferred Track *</label>
                        <select id="preferred_track" name="preferred_track" required>
                            <option value="">Select Track</option>
                            <option value="writing">Writing</option>
                            <option value="video_editing">Video Editing</option>
                            <option value="graphics_design">Graphics Design</option>
                            <option value="voiceovers">Voiceovers</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="experience_level">Experience level in most preferred track *</label>
                        <select id="experience_level" name="experience_level" required>
                            <option value="">Select Experience Level</option>
                            <option value="novice">Novice</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="professional">Professional</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Which digital tools or software are you comfortable using? *</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="digital_tools[]" value="canva"> Canva</label>
                            <label><input type="checkbox" name="digital_tools[]" value="coreldraw"> CorelDraw</label>
                            <label><input type="checkbox" name="digital_tools[]" value="photoshop"> Photoshop</label>
                            <label><input type="checkbox" name="digital_tools[]" value="illustrator"> Illustrator</label>
                            <label><input type="checkbox" name="digital_tools[]" value="indesign"> InDesign</label>
                            <label><input type="checkbox" name="digital_tools[]" value="figma"> Figma</label>
                            <label><input type="checkbox" name="digital_tools[]" value="sketch"> Sketch</label>
                            <label><input type="checkbox" name="digital_tools[]" value="premiere_pro"> Premiere Pro</label>
                            <label><input type="checkbox" name="digital_tools[]" value="after_effects"> After Effects</label>
                            <label><input type="checkbox" name="digital_tools[]" value="davinci_resolve"> DaVinci Resolve</label>
                            <label><input type="checkbox" name="digital_tools[]" value="filmora"> Filmora</label>
                            <label><input type="checkbox" name="digital_tools[]" value="final_cut_pro"> Final Cut Pro</label>
                            <label><input type="checkbox" name="digital_tools[]" value="audacity"> Audacity</label>
                            <label><input type="checkbox" name="digital_tools[]" value="other"> Other</label>
                        </div>
                        <input type="text" name="digital_tools_other" placeholder="Specify other tools" style="margin-top: 10px; display: none;" id="digital_tools_other">
                    </div>
                </div>

                <!-- Section 5: Availability & Resources -->
                <div class="form-section">
                    <h3>Section 5: Availability & Resources</h3>

                    <div class="form-group">
                        <label>Do you have access to a personal laptop or desktop computer? *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="access_computer" value="yes" required> Yes</label>
                            <label><input type="radio" name="access_computer" value="no" required> No</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Do you have access to reliable internet? *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="access_internet" value="yes" required> Yes</label>
                            <label><input type="radio" name="access_internet" value="no" required> No</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="availability">Are you available to undertake the required 3-month internship schedule (10am–5pm, Mon–Fri, including host placements)? *</label>
                        <select id="availability" name="availability" required>
                            <option value="">Select Availability</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                            <option value="maybe">Maybe</option>
                        </select>
                    </div>
                </div>

                <!-- Section 6: Portfolio & Experience -->
                <div class="form-section">
                    <h3>Section 6: Portfolio & Experience</h3>

                    <div class="form-group">
                        <label>Do you have any previous work, projects, or samples to share?</label>
                        <div class="radio-group">
                            <label><input type="radio" name="has_portfolio" value="yes"> Yes</label>
                            <label><input type="radio" name="has_portfolio" value="no"> No</label>
                        </div>
                    </div>

                    <div class="form-group" id="portfolio_link_group" style="display: none;">
                        <label for="portfolio_link">If yes, please attach a link to your portfolio/Google Drive/online samples</label>
                        <input type="url" id="portfolio_link" name="portfolio_link">
                    </div>

                    <div class="form-group">
                        <label for="previous_experience">Have you had any previous internship, freelance, or work experience in your chosen track?</label>
                        <textarea id="previous_experience" name="previous_experience" rows="4"></textarea>
                    </div>
                </div>

                <!-- Section 7: Commitment & Declaration -->
                <div class="form-section">
                    <h3>Section 7: Commitment & Declaration</h3>

                    <div class="form-group">
                        <label for="heard_about">How did you hear about this internship? *</label>
                        <select id="heard_about" name="heard_about" required>
                            <option value="">Select Option</option>
                            <option value="website">Website</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="referral">Referral</option>
                            <option value="social_media">Social Media (Facebook, Instagram, Twitter)</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group" id="heard_other_group" style="display: none;">
                        <label for="heard_other">Please specify how you heard about this internship</label>
                        <input type="text" id="heard_other" name="heard_other">
                    </div>

                    <div class="form-group">
                        <label>Declaration *</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="declaration_read" value="yes" required> I have read and understood the <strong>Program Handbook</strong> and <strong>Code of Conduct</strong>.</label>
                            <label><input type="checkbox" name="declaration_commit" value="yes" required> I agree to commit fully to the internship program.</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full_name_signature">Full Name (as Signature) *</label>
                        <input type="text" id="full_name_signature" name="full_name_signature" required>
                    </div>
                </div>

                <div class="form-submit">
                    <button type="submit" class="submit-btn">Submit Application</button>
                    <div class="loading" style="display: none;">Submitting...</div>
                </div>
            </form>

            <div id="form-message" class="form-message" style="display: none;"></div>
        </div>
    <?php
    }

    public function handle_form_submission()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['fisibul_nonce'], 'fisibul_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fisibul_applications';

        // Sanitize and validate form data
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'middle_name' => sanitize_text_field($_POST['middle_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'address' => sanitize_textarea_field($_POST['address']),
            'education_level' => sanitize_text_field($_POST['education_level']),
            'education_other' => sanitize_text_field($_POST['education_other']),
            'institution' => sanitize_text_field($_POST['institution']),
            'course_study' => sanitize_text_field($_POST['course_study']),
            'passion_content' => sanitize_text_field($_POST['passion_content']),
            'interest_reason' => sanitize_textarea_field($_POST['interest_reason']),
            'career_goals' => sanitize_textarea_field($_POST['career_goals']),
            'preferred_track' => sanitize_text_field($_POST['preferred_track']),
            'experience_level' => sanitize_text_field($_POST['experience_level']),
            'digital_tools' => isset($_POST['digital_tools']) ? implode(',', array_map('sanitize_text_field', $_POST['digital_tools'])) : '',
            'access_computer' => sanitize_text_field($_POST['access_computer']),
            'access_internet' => sanitize_text_field($_POST['access_internet']),
            'availability' => sanitize_text_field($_POST['availability']),
            'has_portfolio' => sanitize_text_field($_POST['has_portfolio']),
            'portfolio_link' => esc_url_raw($_POST['portfolio_link']),
            'previous_experience' => sanitize_textarea_field($_POST['previous_experience']),
            'heard_about' => sanitize_text_field($_POST['heard_about']),
            'heard_other' => sanitize_text_field($_POST['heard_other']),
            'declaration_agreed' => 'yes', // Combined from checkboxes
            'full_name_signature' => sanitize_text_field($_POST['full_name_signature'])
        );

        // Check if email already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $data['email']
        ));

        if ($existing) {
            wp_send_json_error('Email already registered. Please use a different email address.');
            return;
        }

        // Insert data
        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_send_json_error('Failed to submit application. Please try again.');
        } else {
            wp_send_json_success('Application submitted successfully! We will contact you if you are shortlisted.');
        }
    }

    public function admin_page_applications()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fisibul_applications';

        // Handle status updates
        if (isset($_POST['update_status'])) {
            $id = intval($_POST['application_id']);
            $status = sanitize_text_field($_POST['status']);

            $wpdb->update(
                $table_name,
                array('status' => $status),
                array('id' => $id),
                array('%s'),
                array('%d')
            );
        }

        // Get all applications
        $applications = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_date DESC");
        $total_applications = count($applications);
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $approved = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");
        $rejected = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'");

    ?>
        <div class="wrap">
            <h1>Fisibul Internship Applications</h1>

            <div class="fisibul-stats">
                <div class="stat-box">
                    <h3><?php echo $total_applications; ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $approved; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $rejected; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Track</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo $app->id; ?></td>
                            <td><?php echo esc_html($app->first_name . ' ' . $app->last_name); ?></td>
                            <td><?php echo esc_html($app->email); ?></td>
                            <td><?php echo esc_html($app->phone); ?></td>
                            <td><?php echo esc_html($app->preferred_track); ?></td>
                            <td>
                                <span class="status-<?php echo $app->status; ?>">
                                    <?php echo ucfirst($app->status); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($app->submission_date)); ?></td>
                            <td>
                                <a href="#" onclick="viewApplication(<?php echo $app->id; ?>)" class="button">View</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app->id; ?>">
                                    <select name="status">
                                        <option value="pending" <?php selected($app->status, 'pending'); ?>>Pending</option>
                                        <option value="approved" <?php selected($app->status, 'approved'); ?>>Approved</option>
                                        <option value="rejected" <?php selected($app->status, 'rejected'); ?>>Rejected</option>
                                    </select>
                                    <input type="submit" name="update_status" value="Update" class="button button-small">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Application Details Modal -->
        <div id="application-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="modal-body"></div>
            </div>
        </div>
    <?php
    }

    public function admin_page_export()
    {
    ?>
        <div class="wrap">
            <h1>Export Applications</h1>

            <div class="export-options">
                <h3>Export Options</h3>

                <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                    <input type="hidden" name="action" value="export_applications">
                    <?php wp_nonce_field('fisibul_export', 'export_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th>Export Format</th>
                            <td>
                                <select name="format">
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Status Filter</th>
                            <td>
                                <select name="status_filter">
                                    <option value="all">All Applications</option>
                                    <option value="pending">Pending Only</option>
                                    <option value="approved">Approved Only</option>
                                    <option value="rejected">Rejected Only</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Date Range</th>
                            <td>
                                From: <input type="date" name="date_from">
                                To: <input type="date" name="date_to">
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button-primary" value="Export Applications">
                    </p>
                </form>
            </div>

            <div class="export-info">
                <h3>Export Information</h3>
                <p>The export will include all application data including:</p>
                <ul>
                    <li>Personal Information</li>
                    <li>Educational Background</li>
                    <li>Track Preferences and Skills</li>
                    <li>Portfolio Links</li>
                    <li>Application Status</li>
                    <li>Submission Dates</li>
                </ul>
            </div>
        </div>
<?php
    }

    public function export_to_excel()
    {
        if (!wp_verify_nonce($_POST['export_nonce'], 'fisibul_export')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fisibul_applications';

        // Build query based on filters
        $where_conditions = array('1=1');
        $where_values = array();

        if (!empty($_POST['status_filter']) && $_POST['status_filter'] !== 'all') {
            $where_conditions[] = 'status = %s';
            $where_values[] = $_POST['status_filter'];
        }

        if (!empty($_POST['date_from'])) {
            $where_conditions[] = 'DATE(submission_date) >= %s';
            $where_values[] = $_POST['date_from'];
        }

        if (!empty($_POST['date_to'])) {
            $where_conditions[] = 'DATE(submission_date) <= %s';
            $where_values[] = $_POST['date_to'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        if (!empty($where_values)) {
            $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE $where_clause ORDER BY submission_date DESC", $where_values);
        } else {
            $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY submission_date DESC";
        }

        $applications = $wpdb->get_results($sql, ARRAY_A);

        $format = $_POST['format'];

        if ($format === 'excel') {
            $this->export_excel($applications);
        } else {
            $this->export_csv($applications);
        }
    }

    private function export_excel($applications)
    {
        // Set headers for Excel download
        $filename = 'fisibul_internship_applications_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Create a simple Excel-like format using HTML table
        // For a more robust solution, you'd want to use a library like PhpSpreadsheet
        echo '<html><head><meta charset="UTF-8"><title>Fisibul Applications</title></head><body>';
        echo '<table border="1">';

        // Headers
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>First Name</th>';
        echo '<th>Middle Name</th>';
        echo '<th>Last Name</th>';
        echo '<th>Email</th>';
        echo '<th>Phone</th>';
        echo '<th>Date of Birth</th>';
        echo '<th>Address</th>';
        echo '<th>Education Level</th>';
        echo '<th>Education Other</th>';
        echo '<th>Institution</th>';
        echo '<th>Course of Study</th>';
        echo '<th>Passion for Content</th>';
        echo '<th>Interest Reason</th>';
        echo '<th>Career Goals</th>';
        echo '<th>Preferred Track</th>';
        echo '<th>Experience Level</th>';
        echo '<th>Digital Tools</th>';
        echo '<th>Access Computer</th>';
        echo '<th>Access Internet</th>';
        echo '<th>Availability</th>';
        echo '<th>Has Portfolio</th>';
        echo '<th>Portfolio Link</th>';
        echo '<th>Previous Experience</th>';
        echo '<th>Heard About</th>';
        echo '<th>Heard Other</th>';
        echo '<th>Declaration Agreed</th>';
        echo '<th>Full Name Signature</th>';
        echo '<th>Submission Date</th>';
        echo '<th>Status</th>';
        echo '</tr>';

        // Data rows
        foreach ($applications as $app) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($app['id']) . '</td>';
            echo '<td>' . htmlspecialchars($app['first_name']) . '</td>';
            echo '<td>' . htmlspecialchars($app['middle_name']) . '</td>';
            echo '<td>' . htmlspecialchars($app['last_name']) . '</td>';
            echo '<td>' . htmlspecialchars($app['email']) . '</td>';
            echo '<td>' . htmlspecialchars($app['phone']) . '</td>';
            echo '<td>' . htmlspecialchars($app['date_of_birth']) . '</td>';
            echo '<td>' . htmlspecialchars($app['address']) . '</td>';
            echo '<td>' . htmlspecialchars($app['education_level']) . '</td>';
            echo '<td>' . htmlspecialchars($app['education_other']) . '</td>';
            echo '<td>' . htmlspecialchars($app['institution']) . '</td>';
            echo '<td>' . htmlspecialchars($app['course_study']) . '</td>';
            echo '<td>' . htmlspecialchars($app['passion_content']) . '</td>';
            echo '<td>' . htmlspecialchars($app['interest_reason']) . '</td>';
            echo '<td>' . htmlspecialchars($app['career_goals']) . '</td>';
            echo '<td>' . htmlspecialchars($app['preferred_track']) . '</td>';
            echo '<td>' . htmlspecialchars($app['experience_level']) . '</td>';
            echo '<td>' . htmlspecialchars($app['digital_tools']) . '</td>';
            echo '<td>' . htmlspecialchars($app['access_computer']) . '</td>';
            echo '<td>' . htmlspecialchars($app['access_internet']) . '</td>';
            echo '<td>' . htmlspecialchars($app['availability']) . '</td>';
            echo '<td>' . htmlspecialchars($app['has_portfolio']) . '</td>';
            echo '<td>' . htmlspecialchars($app['portfolio_link']) . '</td>';
            echo '<td>' . htmlspecialchars($app['previous_experience']) . '</td>';
            echo '<td>' . htmlspecialchars($app['heard_about']) . '</td>';
            echo '<td>' . htmlspecialchars($app['heard_other']) . '</td>';
            echo '<td>' . htmlspecialchars($app['declaration_agreed']) . '</td>';
            echo '<td>' . htmlspecialchars($app['full_name_signature']) . '</td>';
            echo '<td>' . htmlspecialchars($app['submission_date']) . '</td>';
            echo '<td>' . htmlspecialchars($app['status']) . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    private function export_csv($applications)
    {
        $filename = 'fisibul_internship_applications_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, array(
            'ID',
            'First Name',
            'Middle Name',
            'Last Name',
            'Email',
            'Phone',
            'Date of Birth',
            'Address',
            'Education Level',
            'Education Other',
            'Institution',
            'Course of Study',
            'Passion for Content',
            'Interest Reason',
            'Career Goals',
            'Preferred Track',
            'Experience Level',
            'Digital Tools',
            'Access Computer',
            'Access Internet',
            'Availability',
            'Has Portfolio',
            'Portfolio Link',
            'Previous Experience',
            'Heard About',
            'Heard Other',
            'Declaration Agreed',
            'Full Name Signature',
            'Submission Date',
            'Status'
        ));

        // CSV Data
        foreach ($applications as $app) {
            fputcsv($output, array(
                $app['id'],
                $app['first_name'],
                $app['middle_name'],
                $app['last_name'],
                $app['email'],
                $app['phone'],
                $app['date_of_birth'],
                $app['address'],
                $app['education_level'],
                $app['education_other'],
                $app['institution'],
                $app['course_study'],
                $app['passion_content'],
                $app['interest_reason'],
                $app['career_goals'],
                $app['preferred_track'],
                $app['experience_level'],
                $app['digital_tools'],
                $app['access_computer'],
                $app['access_internet'],
                $app['availability'],
                $app['has_portfolio'],
                $app['portfolio_link'],
                $app['previous_experience'],
                $app['heard_about'],
                $app['heard_other'],
                $app['declaration_agreed'],
                $app['full_name_signature'],
                $app['submission_date'],
                $app['status']
            ));
        }

        fclose($output);
        exit;
    }
}

// Initialize the plugin
new FisibulInternshipPlugin();
