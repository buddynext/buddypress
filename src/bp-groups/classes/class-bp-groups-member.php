<?php
/**
 * BuddyPress Groups Classes.
 *
 * @package BuddyPress
 * @subpackage Groups
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Group Membership object.
 */
class BP_Groups_Member {

	/**
	 * ID of the membership.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $id = 0;

	/**
	 * ID of the group associated with the membership.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $group_id = 0;

	/**
	 * ID of the user associated with the membership.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $user_id = 0;

	/**
	 * ID of the user whose invitation initiated the membership.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $inviter_id = 0;

	/**
	 * Whether the member is an admin of the group.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $is_admin = 0;

	/**
	 * Whether the member is a mod of the group.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $is_mod = 0;

	/**
	 * Whether the member is banned from the group.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $is_banned = 0;

	/**
	 * Title used to describe the group member's role in the group.
	 *
	 * Eg, 'Group Admin'.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public string $user_title = '';

	/**
	 * Last modified date of the membership.
	 *
	 * This value is updated when, eg, invitations are accepted.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public string $date_modified = '';

	/**
	 * Whether the membership has been confirmed.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $is_confirmed = 0;

	/**
	 * Comments associated with the membership.
	 *
	 * In BP core, these are limited to the optional message users can
	 * include when requesting membership to a private group.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public string $comments = '';

	/**
	 * Whether an invitation has been sent for this membership.
	 *
	 * The purpose of this flag is to mark when an invitation has been
	 * "drafted" (the user has been added via the interface at Send
	 * Invites), but the Send button has not been pressed, so the
	 * invitee has not yet been notified.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public int $invite_sent = 0;

	/**
	 * WP_User object representing the membership's user.
	 *
	 * @since 1.6.0
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Constructor method.
	 *
	 * @since 1.6.0
	 *
	 * @param int      $user_id  Optional. Along with $group_id, can be used to
	 *                           look up a membership.
	 * @param int      $group_id Optional. Along with $user_id, can be used to
	 *                           look up a membership.
	 * @param int|bool $id       Optional. The unique ID of the membership object.
	 * @param bool     $populate Whether to populate the properties of the
	 *                           located membership. Default: true.
	 */
	public function __construct( $user_id = 0, $group_id = 0, $id = false, $populate = true ) {

		// User and group are not empty, and ID is.
		if ( ! empty( $user_id ) && ! empty( $group_id ) && empty( $id ) ) {
			$this->user_id  = $user_id;
			$this->group_id = $group_id;

			if ( ! empty( $populate ) ) {
				$this->populate();
			}
		}

		// ID is not empty.
		if ( ! empty( $id ) ) {
			$this->id = $id;

			if ( ! empty( $populate ) ) {
				$this->populate();
			}
		}
	}

	/**
	 * Populate the object's properties.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		if ( $this->user_id && $this->group_id && ! $this->id ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );
		}

		if ( ! empty( $this->id ) ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE id = %d", $this->id );
		}

		$member = $wpdb->get_row( $sql );

		if ( ! empty( $member ) ) {
			$this->id            = (int) $member->id;
			$this->group_id      = (int) $member->group_id;
			$this->user_id       = (int) $member->user_id;
			$this->inviter_id    = (int) $member->inviter_id;
			$this->is_admin      = (int) $member->is_admin;
			$this->is_mod        = (int) $member->is_mod;
			$this->is_banned     = (int) $member->is_banned;
			$this->user_title    = $member->user_title;
			$this->date_modified = $member->date_modified;
			$this->is_confirmed  = (int) $member->is_confirmed;
			$this->comments      = $member->comments;
			$this->invite_sent   = (int) $member->invite_sent;
		}
	}

	/**
	 * Magic getter.
	 *
	 * @since 2.8.0
	 *
	 * @param string $key Key.
	 * @return BP_Core_User|null
	 */
	public function __get( $key ) {
		if ( $key == 'user' ) {
			// @todo fix this.
			return $this->get_user_object( $this->user_id );
		}
	}

	/**
	 * Magic issetter.
	 *
	 * @since 2.8.0
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	public function __isset( $key ) {
		switch ( $key ) {
			case 'user':
				return true;

			default:
				return isset( $this->{$key} );
		}
	}

	/**
	 * Get the user object corresponding to this membership.
	 *
	 * Used for lazyloading the protected `user` property.
	 *
	 * @since 2.8.0
	 *
	 * @return BP_Core_User
	 */
	protected function get_user_object() {
		if ( empty( $this->user ) ) {
			$this->user = new BP_Core_User( $this->user_id );
		}

		return $this->user;
	}

	/**
	 * Save the membership data to the database.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->user_id       = apply_filters( 'groups_member_user_id_before_save', $this->user_id, $this->id );
		$this->group_id      = apply_filters( 'groups_member_group_id_before_save', $this->group_id, $this->id );
		$this->inviter_id    = apply_filters( 'groups_member_inviter_id_before_save', $this->inviter_id, $this->id );
		$this->is_admin      = apply_filters( 'groups_member_is_admin_before_save', $this->is_admin, $this->id );
		$this->is_mod        = apply_filters( 'groups_member_is_mod_before_save', $this->is_mod, $this->id );
		$this->is_banned     = apply_filters( 'groups_member_is_banned_before_save', $this->is_banned, $this->id );
		$this->user_title    = apply_filters( 'groups_member_user_title_before_save', $this->user_title, $this->id );
		$this->date_modified = apply_filters( 'groups_member_date_modified_before_save', $this->date_modified, $this->id );
		$this->is_confirmed  = apply_filters( 'groups_member_is_confirmed_before_save', $this->is_confirmed, $this->id );
		$this->comments      = apply_filters( 'groups_member_comments_before_save', $this->comments, $this->id );
		$this->invite_sent   = apply_filters( 'groups_member_invite_sent_before_save', $this->invite_sent, $this->id );

		/**
		 * Fires before the current group membership item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Groups_Member $group_membership Current instance of the group membership item being saved. Passed by reference.
		 */
		do_action_ref_array( 'groups_member_before_save', array( &$this ) );

		// The following properties are required; bail if not met.
		if ( empty( $this->user_id ) || empty( $this->group_id ) ) {
			return false;
		}

		if ( ! empty( $this->id ) ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->groups->table_name_members} SET inviter_id = %d, is_admin = %d, is_mod = %d, is_banned = %d, user_title = %s, date_modified = %s, is_confirmed = %d, comments = %s, invite_sent = %d WHERE id = %d", $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent, $this->id );
		} else {
			// Ensure that user is not already a member of the group before inserting.
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 1 LIMIT 1", $this->user_id, $this->group_id ) ) ) {
				return false;
			}

			$sql = $wpdb->prepare( "INSERT INTO {$bp->groups->table_name_members} ( user_id, group_id, inviter_id, is_admin, is_mod, is_banned, user_title, date_modified, is_confirmed, comments, invite_sent ) VALUES ( %d, %d, %d, %d, %d, %d, %s, %s, %d, %s, %d )", $this->user_id, $this->group_id, $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent );
		}

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		$this->id = $wpdb->insert_id;

		// Update the user's group count.
		self::refresh_total_group_count_for_user( $this->user_id );

		/**
		 * Fires after the current group membership item has been saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Groups_Member $member Current instance of the group membership item has been saved. Passed by reference.
		 */
		do_action_ref_array( 'groups_member_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Promote a member to a new status.
	 *
	 * @since 1.6.0
	 *
	 * @param string $status The new status. 'mod' or 'admin'.
	 * @return bool
	 */
	public function promote( $status = 'mod' ) {
		if ( 'mod' == $status ) {
			$this->is_admin   = 0;
			$this->is_mod     = 1;
			$this->user_title = __( 'Group Mod', 'buddypress' );
		}

		if ( 'admin' == $status ) {
			$this->is_admin   = 1;
			$this->is_mod     = 0;
			$this->user_title = __( 'Group Admin', 'buddypress' );
		}

		return $this->save();
	}

	/**
	 * Demote membership to Member status (non-admin, non-mod).
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function demote() {
		$this->is_mod     = 0;
		$this->is_admin   = 0;
		$this->user_title = false;

		return $this->save();
	}

	/**
	 * Ban the user from the group.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function ban() {
		if ( ! empty( $this->is_admin ) ) {
			return false;
		}

		$this->is_mod    = 0;
		$this->is_banned = 1;

		return $this->save();
	}

	/**
	 * Unban the user from the group.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function unban() {
		if ( ! empty( $this->is_admin ) ) {
			return false;
		}

		$this->is_banned = 0;

		return $this->save();
	}

	/**
	 * Mark a pending invitation as accepted.
	 *
	 * @since 1.6.0
	 */
	public function accept_invite() {
		$this->inviter_id    = 0;
		$this->is_confirmed  = 1;
		$this->date_modified = bp_core_current_time();
	}

	/**
	 * Confirm a membership request.
	 *
	 * @since 1.6.0
	 */
	public function accept_request() {
		$this->is_confirmed  = 1;
		$this->date_modified = bp_core_current_time();
	}

	/**
	 * Remove the current membership.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return bool
	 */
	public function remove() {
		global $wpdb;

		/**
		 * Fires before a member is removed from a group.
		 *
		 * @since 2.3.0
		 *
		 * @param BP_Groups_Member $group_membership Current group membership class.
		 */
		do_action_ref_array( 'groups_member_before_remove', array( $this ) );

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );

		if ( ! $result = $wpdb->query( $sql ) ) {
			return false;
		}

		// Update the user's group count.
		self::refresh_total_group_count_for_user( $this->user_id );

		/**
		 * Fires after a member is removed from a group.
		 *
		 * @since 2.3.0
		 *
		 * @param BP_Groups_Member $member Current group membership object.
		 */
		do_action_ref_array( 'groups_member_after_remove', array( $this ) );

		return $result;
	}

	/** Static Methods ****************************************************/

	/**
	 * Refresh the `total_group_count` for a user.
	 *
	 * @since 1.8.0
	 *
	 * @param int $user_id ID of the user.
	 */
	public static function refresh_total_group_count_for_user( $user_id ) {
		bp_update_user_meta( $user_id, 'total_group_count', (int) self::total_group_count( $user_id ) );
	}

	/**
	 * Refresh the `total_member_count` for a group.
	 *
	 * The request skip the current cache so that we always grab the latest total count.
	 *
	 * @since 1.8.0
	 * @since 10.0.0 Updated to use `BP_Groups_Group::get_total_member_count`
	 *
	 * @param int $group_id ID of the group.
	 */
	public static function refresh_total_member_count_for_group( $group_id ) {
		BP_Groups_Group::get_total_member_count( $group_id, true );
	}

	/**
	 * Delete a membership, based on user + group IDs.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return bool
	 */
	public static function delete( $user_id, $group_id ) {
		global $wpdb;

		/**
		 * Fires before a group membership is deleted.
		 *
		 * @since 2.3.0
		 *
		 * @param int $user_id  ID of the user.
		 * @param int $group_id ID of the group.
		 */
		do_action( 'bp_groups_member_before_delete', $user_id, $group_id );

		$bp     = buddypress();
		$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );

		// Update the user's group count.
		self::refresh_total_group_count_for_user( $user_id );

		/**
		 * Fires after a member is removed from a group.
		 *
		 * @since 2.3.0
		 *
		 * @param int $user_id  ID of the user.
		 * @param int $group_id ID of the group.
		 */
		do_action( 'bp_groups_member_after_delete', $user_id, $group_id );

		return (bool) $remove;
	}

	/**
	 * Get the IDs of the groups of which a specified user is a member.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int      $user_id ID of the user.
	 * @param int|bool $limit   Optional. Max number of results to return.
	 *                          Default: false (no limit).
	 * @param int|bool $page    Optional. Page offset of results to return.
	 *                          Default: false (no limit).
	 * @return array {
	 *     @type array $groups Array of groups returned by paginated query.
	 *     @type int   $total  Count of groups matching query.
	 * }
	 */
	public static function get_group_ids( $user_id, $limit = false, $page = false ) {
		global $wpdb;

		$pag_sql = '';
		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
		}

		$bp = buddypress();

		// If the user is logged in and viewing their own groups, we can show hidden and private groups.
		if ( $user_id != bp_loggedin_user_id() ) {
			$group_sql    = $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0{$pag_sql}", $user_id );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		} else {
			$group_sql    = $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0{$pag_sql}", $user_id );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT group_id) FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id ) );
		}

		$groups = $wpdb->get_col( $group_sql );

		return array(
			'groups' => $groups,
			'total'  => (int) $total_groups,
		);
	}

	/**
	 * Get the IDs of the groups of which a specified user is a member, sorted by the date joined.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int         $user_id ID of the user.
	 * @param int|bool    $limit   Optional. Max number of results to return.
	 *                             Default: false (no limit).
	 * @param int|bool    $page    Optional. Page offset of results to return.
	 *                             Default: false (no limit).
	 * @param string|bool $filter  Optional. Limit results to groups whose name or
	 *                             description field matches search terms.
	 * @return array {
	 *     @type array $groups Array of groups returned by paginated query.
	 *     @type int   $total  Count of groups matching query.
	 * }
	 */
	public static function get_recently_joined( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb;

		$pag_sql     = $hidden_sql = $filter_sql = '';
		$user_id_sql = $wpdb->prepare( 'm.user_id = %d', $user_id );

		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
		}

		if ( ! empty( $filter ) ) {
			$search_terms_like = '%' . bp_esc_like( $filter ) . '%';
			$filter_sql        = $wpdb->prepare( ' AND ( g.name LIKE %s OR g.description LIKE %s )', $search_terms_like, $search_terms_like );
		}

		if ( $user_id != bp_loggedin_user_id() ) {
			$hidden_sql = " AND g.status != 'hidden'";
		}

		$bp = buddypress();

		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY m.date_modified DESC {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_banned = 0 AND m.is_confirmed = 1 ORDER BY m.date_modified DESC" );

		return array(
			'groups' => $paged_groups,
			'total'  => $total_groups,
		);
	}

	/**
	 * Get the IDs of the groups of which a specified user is an admin.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int         $user_id ID of the user.
	 * @param int|bool    $limit   Optional. Max number of results to return.
	 *                             Default: false (no limit).
	 * @param int|bool    $page    Optional. Page offset of results to return.
	 *                             Default: false (no limit).
	 * @param string|bool $filter  Optional. Limit results to groups whose name or
	 *                             description field matches search terms.
	 * @return array {
	 *     @type array $groups Array of groups returned by paginated query.
	 *     @type int   $total  Count of groups matching query.
	 * }
	 */
	public static function get_is_admin_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb;

		$pag_sql     = $hidden_sql = $filter_sql = '';
		$user_id_sql = $wpdb->prepare( 'm.user_id = %d', $user_id );

		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
		}

		if ( ! empty( $filter ) ) {
			$search_terms_like = '%' . bp_esc_like( $filter ) . '%';
			$filter_sql        = $wpdb->prepare( ' AND ( g.name LIKE %s OR g.description LIKE %s )', $search_terms_like, $search_terms_like );
		}

		if ( $user_id != bp_loggedin_user_id() ) {
			$hidden_sql = " AND g.status != 'hidden'";
		}

		$bp = buddypress();

		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY m.date_modified ASC {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY date_modified ASC" );

		return array(
			'groups' => $paged_groups,
			'total'  => $total_groups,
		);
	}

	/**
	 * Get the IDs of the groups of which a specified user is a moderator.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int         $user_id ID of the user.
	 * @param int|bool    $limit   Optional. Max number of results to return.
	 *                             Default: false (no limit).
	 * @param int|bool    $page    Optional. Page offset of results to return.
	 *                             Default: false (no limit).
	 * @param string|bool $filter  Optional. Limit results to groups whose name or
	 *                             description field matches search terms.
	 * @return array {
	 *     @type array $groups Array of groups returned by paginated query.
	 *     @type int   $total  Count of groups matching query.
	 * }
	 */
	public static function get_is_mod_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb;

		$user_id_sql = $pag_sql = $hidden_sql = $filter_sql = '';

		$user_id_sql = $wpdb->prepare( 'm.user_id = %d', $user_id );

		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
		}

		if ( ! empty( $filter ) ) {
			$search_terms_like = '%' . bp_esc_like( $filter ) . '%';
			$filter_sql        = $wpdb->prepare( ' AND ( g.name LIKE %s OR g.description LIKE %s )', $search_terms_like, $search_terms_like );
		}

		if ( $user_id != bp_loggedin_user_id() ) {
			$hidden_sql = " AND g.status != 'hidden'";
		}

		$bp = buddypress();

		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY m.date_modified ASC {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY date_modified ASC" );

		return array(
			'groups' => $paged_groups,
			'total'  => $total_groups,
		);
	}

	/**
	 * Get the groups of which a specified user is banned from.
	 *
	 * @since 2.4.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int         $user_id ID of the user.
	 * @param int|bool    $limit   Optional. Max number of results to return.
	 *                             Default: false (no limit).
	 * @param int|bool    $page    Optional. Page offset of results to return.
	 *                             Default: false (no limit).
	 * @param string|bool $filter  Optional. Limit results to groups whose name or
	 *                             description field matches search terms.
	 * @return array {
	 *     @type array $groups Array of groups returned by paginated query.
	 *     @type int   $total  Count of groups matching query.
	 * }
	 */
	public static function get_is_banned_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb;

		$bp = buddypress();

		$user_id_sql = $pag_sql = $hidden_sql = $filter_sql = '';
		$user_id_sql = $wpdb->prepare( 'm.user_id = %d', $user_id );

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
		}

		if ( $filter ) {
			$search_terms_like = '%' . bp_esc_like( $filter ) . '%';
			$filter_sql        = $wpdb->prepare( ' AND ( g.name LIKE %s OR g.description LIKE %s )', $search_terms_like, $search_terms_like );
		}

		if ( $user_id !== bp_loggedin_user_id() && ! bp_current_user_can( 'bp_moderate' ) ) {
			$hidden_sql = " AND g.status != 'hidden'";
		}

		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_banned = 1  ORDER BY m.date_modified ASC {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND {$user_id_sql} AND m.is_banned = 1 ORDER BY date_modified ASC" );

		return array(
			'groups' => $paged_groups,
			'total'  => $total_groups,
		);
	}

	/**
	 * Get the count of groups of which the specified user is a member.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id Optional. Default: ID of the displayed user.
	 * @return int Group count.
	 */
	public static function total_group_count( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		$bp = buddypress();

		if ( $user_id != bp_loggedin_user_id() && ! bp_current_user_can( 'bp_moderate' ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
	}

	/**
	 * Get group objects for groups that a user is currently invited to.
	 *
	 * @since 1.6.0
	 *
	 * @param int               $user_id ID of the invitee.
	 * @param int|bool          $limit   Optional. Max number of results to return.
	 *                                   Default: false (no limit).
	 * @param int|bool          $page    Optional. Page offset of results to return.
	 *                                   Default: false (no limit).
	 * @param string|array|bool $exclude Optional. Array or comma-separated list
	 *                                   of group IDs to exclude from results.
	 * @return array {
	 *     @type array $groups Array of groups returned by paginated query.
	 *     @type int   $total  Count of groups matching query.
	 * }
	 */
	public static function get_invites( $user_id, $limit = false, $page = false, $exclude = false ) {
		return groups_get_invites_for_user( $user_id, $limit, $page, $exclude );
	}

	/**
	 * Gets the total group invite count for a user.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public static function get_invite_count_for_user( $user_id = 0 ) {
		return groups_get_invite_count_for_user( $user_id );
	}

	/**
	 * Gets memberships of a user for purposes of a personal data export.
	 *
	 * @since 4.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int   $user_id ID of the user.
	 * @param array $args {
	 *    Array of optional arguments.
	 *    @type int    $page     Page of memberships being requested. Default 1.
	 *    @type int    $per_page Memberships to return per page. Default 20.
	 *    @type string $type     Membership type being requested. Accepts 'membership',
	 *                           'pending_request', 'pending_received_invitation',
	 *                           'pending_sent_invitation'. Default 'membership'.
	 * }
	 *
	 * @return array
	 */
	public static function get_user_memberships( $user_id, $args = array() ) {
		global $wpdb;

		$bp = buddypress();

		$r = array_merge(
			array(
				'page'     => 1,
				'per_page' => 20,
				'type'     => 'membership',
			),
			$args
		);

		$sql = array(
			'select' => 'SELECT *',
			'from'   => "FROM {$bp->groups->table_name_members}",
			'where'  => '',
			'limits' => '',
		);

		switch ( $r['type'] ) {
			case 'pending_request':
				return groups_get_requests(
					array(
						'user_id'  => $user_id,
						'page'     => $r['page'],
						'per_page' => $r['per_page'],
					)
				);

			case 'pending_received_invitation':
				return groups_get_invites(
					array(
						'user_id'  => $user_id,
						'page'     => $r['page'],
						'per_page' => $r['per_page'],
					)
				);

			case 'pending_sent_invitation':
				return groups_get_invites(
					array(
						'inviter_id' => $user_id,
						'page'       => $r['page'],
						'per_page'   => $r['per_page'],
					)
				);

			case 'membership':
			default:
				$sql['where'] = $wpdb->prepare( 'user_id = %d AND is_confirmed = 1', $user_id );
				break;
		}

		if ( $r['page'] && $r['per_page'] ) {
			$sql['limits'] = $wpdb->prepare( 'LIMIT %d, %d', ( $r['page'] - 1 ) * $r['per_page'], $r['per_page'] );
		}

		$memberships = $wpdb->get_results( "{$sql['select']} {$sql['from']} WHERE {$sql['where']} {$sql['limits']}" );

		foreach ( $memberships as &$membership ) {
			$membership->id           = (int) $membership->id;
			$membership->group_id     = (int) $membership->group_id;
			$membership->user_id      = (int) $membership->user_id;
			$membership->inviter_id   = (int) $membership->inviter_id;
			$membership->is_admin     = (int) $membership->is_admin;
			$membership->is_mod       = (int) $membership->is_mod;
			$membership->is_banned    = (int) $membership->is_banned;
			$membership->is_confirmed = (int) $membership->is_confirmed;
			$membership->invite_sent  = (int) $membership->invite_sent;
		}

		return $memberships;
	}

	/**
	 * Check whether a user has an outstanding invitation to a given group.
	 *
	 * @since 1.6.0
	 *
	 * @param int    $user_id  ID of the potential invitee.
	 * @param int    $group_id ID of the group.
	 * @param string $type     If 'sent', results are limited to those invitations
	 *                         that have actually been sent (non-draft). Default: 'sent'.
	 * @return int|null The ID of the invitation if found; null if not found.
	 */
	public static function check_has_invite( $user_id, $group_id, $type = 'sent' ) {
		return groups_is_user_invited( $user_id, $group_id, $type );
	}

	/**
	 * Delete an invitation, by specifying user ID and group ID.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param  int $user_id    ID of the user.
	 * @param  int $group_id   ID of the group.
	 * @param  int $inviter_id ID of the inviter. Specify if you want to delete
	 *                         a specific invite. Leave false if you want to
	 *                         delete all invites to this group.
	 * @return int Number of records deleted.
	 */
	public static function delete_invite( $user_id, $group_id, $inviter_id = false ) {
		/**
		 * Fires before a group invitation is deleted.
		 *
		 * @since 2.6.0
		 * @since 5.0.0 Added $inviter_id
		 *
		 * @param int $user_id  ID of the user.
		 * @param int $group_id ID of the group.
		 * @param  int $inviter_id ID of the inviter.
		 */
		do_action( 'bp_groups_member_before_delete_invite', $user_id, $group_id, $inviter_id );

		return groups_delete_invite( $user_id, $group_id, $inviter_id );
	}

	/**
	 * Delete an unconfirmed membership request, by user ID and group ID.
	 *
	 * @since 1.6.0
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return int|false Number of records deleted. False if the user is not a member of the group.
	 */
	public static function delete_request( $user_id, $group_id ) {
		return groups_delete_membership_request( false, $user_id, $group_id );
	}

	/**
	 * Check whether a user is an admin of a given group.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return mixed
	 */
	public static function check_is_admin( $user_id, $group_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return false;
		}

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_admin = 1 AND is_banned = 0", $user_id, $group_id ) );
	}

	/**
	 * Check whether a user is a mod of a given group.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return mixed
	 */
	public static function check_is_mod( $user_id, $group_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return false;
		}

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_mod = 1 AND is_banned = 0", $user_id, $group_id ) );
	}

	/**
	 * Check whether a user is a member of a given group.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return mixed
	 */
	public static function check_is_member( $user_id, $group_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return false;
		}

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id, $group_id ) );
	}

	/**
	 * Check whether a user is banned from a given group.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return int|null int 1 if user is banned; int 0 if user is not banned;
	 *                  null if user is not part of the group or if group doesn't exist.
	 */
	public static function check_is_banned( $user_id, $group_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return false;
		}

		$bp = buddypress();

		$query = $wpdb->get_var( $wpdb->prepare( "SELECT is_banned FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );

		return is_numeric( $query ) ? (int) $query : $query;
	}

	/**
	 * Is the specified user the creator of the group?
	 *
	 * @since 1.2.6
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return int|null int of group ID if user is the creator; null on failure.
	 */
	public static function check_is_creator( $user_id, $group_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return false;
		}

		$bp = buddypress();

		$query = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name} WHERE creator_id = %d AND id = %d", $user_id, $group_id ) );

		return is_numeric( $query ) ? (int) $query : $query;
	}

	/**
	 * Check whether a user has an outstanding membership request for a given group.
	 *
	 * @since 1.6.0
	 *
	 * @param int $user_id  ID of the user.
	 * @param int $group_id ID of the group.
	 * @return int Database ID of the membership if found; int 0 on failure.
	 */
	public static function check_for_membership_request( $user_id, $group_id ) {
		return groups_is_user_pending( $user_id, $group_id );
	}

	/**
	 * Get a list of randomly selected IDs of groups that the member belongs to.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id      ID of the user.
	 * @param int $total_groups Max number of group IDs to return. Default: 5.
	 * @return array Group IDs.
	 */
	public static function get_random_groups( $user_id = 0, $total_groups = 5 ) {
		global $wpdb;

		$bp = buddypress();

		// If the user is logged in and viewing their random groups, we can show hidden and private groups.
		if ( bp_is_my_profile() ) {
			return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand() LIMIT %d", $user_id, $total_groups ) ) );
		}

		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY rand() LIMIT %d", $user_id, $total_groups ) ) );
	}

	/**
	 * Get the IDs of all a given group's members.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $group_id ID of the group.
	 * @return array IDs of all group members.
	 */
	public static function get_group_member_ids( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0", $group_id ) ) );
	}

	/**
	 * Get a list of all a given group's admins.
	 *
	 * @since 1.6.0
	 *
	 * @param  int $group_id ID of the group.
	 * @return array           Info about group admins (user_id + date_modified).
	 */
	public static function get_group_administrator_ids( $group_id ) {
		if ( empty( $group_id ) ) {
			return array();
		}

		$group_admins = wp_cache_get( $group_id, 'bp_group_admins' );

		if ( false === $group_admins ) {
			self::prime_group_admins_mods_cache( array( $group_id ) );
			$group_admins = wp_cache_get( $group_id, 'bp_group_admins' );
		}

		if ( false === $group_admins ) {
			// The wp_cache_get is still coming up empty. Return an empty array.
			$group_admins = array();
		} else {
			// Cast the user_id property as an integer.
			foreach ( (array) $group_admins as $key => $data ) {
				$group_admins[ $key ]->user_id = (int) $group_admins[ $key ]->user_id;
			}
		}

		return $group_admins;
	}

	/**
	 * Prime the bp_group_admins cache for one or more groups.
	 *
	 * @since 2.7.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array $group_ids IDs of the groups.
	 */
	public static function prime_group_admins_mods_cache( $group_ids ) {
		global $wpdb;

		$uncached = bp_get_non_cached_ids( $group_ids, 'bp_group_admins' );

		if ( $uncached ) {
			$bp               = buddypress();
			$uncached_sql     = implode( ',', array_map( 'intval', $uncached ) );
			$group_admin_mods = $wpdb->get_results( "SELECT user_id, group_id, date_modified, is_admin, is_mod FROM {$bp->groups->table_name_members} WHERE group_id IN ({$uncached_sql}) AND ( is_admin = 1 OR is_mod = 1 ) AND is_banned = 0" );

			$admins = $mods = array();
			if ( $group_admin_mods ) {
				foreach ( $group_admin_mods as $group_admin_mod ) {
					$obj                = new stdClass();
					$obj->user_id       = $group_admin_mod->user_id;
					$obj->date_modified = $group_admin_mod->date_modified;

					if ( $group_admin_mod->is_admin ) {
						$admins[ $group_admin_mod->group_id ][] = $obj;
					} else {
						$mods[ $group_admin_mod->group_id ][] = $obj;
					}
				}
			}

			// Prime cache for all groups, even those with no matches.
			foreach ( $uncached as $group_id ) {
				$group_admins = isset( $admins[ $group_id ] ) ? $admins[ $group_id ] : array();
				wp_cache_set( $group_id, $group_admins, 'bp_group_admins' );

				$group_mods = isset( $mods[ $group_id ] ) ? $mods[ $group_id ] : array();
				wp_cache_set( $group_id, $group_mods, 'bp_group_mods' );
			}
		}
	}

	/**
	 * Get a list of all a given group's moderators.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id ID of the group.
	 * @return array Info about group mods (user_id + date_modified).
	 */
	public static function get_group_moderator_ids( $group_id ) {

		if ( empty( $group_id ) ) {
			return array();
		}

		$group_mods = wp_cache_get( $group_id, 'bp_group_mods' );

		if ( false === $group_mods ) {
			self::prime_group_admins_mods_cache( array( $group_id ) );
			$group_mods = wp_cache_get( $group_id, 'bp_group_mods' );
		}

		if ( false === $group_mods ) {
			// The wp_cache_get is still coming up empty. Return an empty array.
			$group_mods = array();
		} else {
			// Cast the user_id property as an integer.
			foreach ( (array) $group_mods as $key => $data ) {
				$group_mods[ $key ]->user_id = (int) $group_mods[ $key ]->user_id;
			}
		}

		return $group_mods;
	}

	/**
	 * Get group membership objects by ID (or an array of IDs).
	 *
	 * @since 2.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int|string|array $membership_ids Single membership ID or comma-separated/array list of membership IDs.
	 * @return array
	 */
	public static function get_memberships_by_id( $membership_ids ) {
		global $wpdb;

		$bp = buddypress();

		$membership_ids = implode( ',', wp_parse_id_list( $membership_ids ) );

		return $wpdb->get_results( "SELECT * FROM {$bp->groups->table_name_members} WHERE id IN ({$membership_ids})" );
	}

	/**
	 * Get the IDs users with outstanding membership requests to the group.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id ID of the group.
	 * @return array IDs of users with outstanding membership requests.
	 */
	public static function get_all_membership_request_user_ids( $group_id ) {
		return groups_get_membership_requested_user_ids( $group_id );
	}

	/**
	 * Get members of a group.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @deprecated 1.6.0
	 *
	 * @param int        $group_id            ID of the group being queried for.
	 * @param bool|int   $limit               Max amount to return.
	 * @param bool|int   $page                Pagination value.
	 * @param bool       $exclude_admins_mods Whether or not to exclude admins and moderators.
	 * @param bool       $exclude_banned      Whether or not to exclude banned members.
	 * @param bool|array $exclude             Array of user IDs to exclude.
	 * @return false|array
	 */
	public static function get_all_for_group( $group_id, $limit = false, $page = false, $exclude_admins_mods = true, $exclude_banned = true, $exclude = false ) {
		global $wpdb;

		_deprecated_function( __METHOD__, '1.8', 'BP_Group_Member_Query' );

		$pag_sql = '';
		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
		}

		$exclude_admins_sql = '';
		if ( ! empty( $exclude_admins_mods ) ) {
			$exclude_admins_sql = 'AND is_admin = 0 AND is_mod = 0';
		}

		$banned_sql = '';
		if ( ! empty( $exclude_banned ) ) {
			$banned_sql = ' AND is_banned = 0';
		}

		$exclude_sql = '';
		if ( ! empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND m.user_id NOT IN ({$exclude})";
		}

		$bp = buddypress();

		if ( bp_is_active( 'xprofile' ) ) {

			/**
			 * Filters the SQL prepared statement used to fetch group members.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value SQL prepared statement for fetching group members.
			 */
			$members = $wpdb->get_results( apply_filters( 'bp_group_members_user_join_filter', $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, pd.value as display_name FROM {$bp->groups->table_name_members} m, {$wpdb->users} u, {$bp->profile->table_name_data} pd WHERE u.ID = m.user_id AND u.ID = pd.user_id AND pd.field_id = 1 AND group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $group_id ) ) );
		} else {

			/** This filter is documented in bp-groups/bp-groups-classes */
			$members = $wpdb->get_results( apply_filters( 'bp_group_members_user_join_filter', $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, u.display_name FROM {$bp->groups->table_name_members} m, {$wpdb->users} u WHERE u.ID = m.user_id AND group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $group_id ) ) );
		}

		if ( empty( $members ) ) {
			return false;
		}

		if ( empty( $pag_sql ) ) {
			$total_member_count = count( $members );
		} else {

			/**
			 * Filters the SQL prepared statement used to fetch group members total count.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value SQL prepared statement for fetching group member count.
			 */
			$total_member_count = $wpdb->get_var( apply_filters( 'bp_group_members_count_user_join_filter', $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->groups->table_name_members} m WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql}", $group_id ) ) );
		}

		// Fetch whether or not the user is a friend.
		foreach ( (array) $members as $user ) {
			$user_ids[] = $user->user_id;
		}

		$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

		if ( bp_is_active( 'friends' ) ) {
			$friend_status = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id IN ( {$user_ids} ) ) OR (initiator_user_id IN ( {$user_ids} ) AND friend_user_id = %d )", bp_loggedin_user_id(), bp_loggedin_user_id() ) );
			for ( $i = 0, $count = count( $members ); $i < $count; ++$i ) {
				foreach ( (array) $friend_status as $status ) {
					if ( $status->initiator_user_id == $members[ $i ]->user_id || $status->friend_user_id == $members[ $i ]->user_id ) {
						$members[ $i ]->is_friend = $status->is_confirmed;
					}
				}
			}
		}

		return array(
			'members' => $members,
			'count'   => $total_member_count,
		);
	}

	/**
	 * Get all membership IDs for a user.
	 *
	 * @since 2.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $user_id ID of the user.
	 * @return array
	 */
	public static function get_membership_ids_for_user( $user_id ) {
		global $wpdb;

		$bp = buddypress();

		$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d ORDER BY id ASC", $user_id ) );

		return $group_ids;
	}

	/**
	 * Delete all memberships for a given group.
	 *
	 * @since 1.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $group_id ID of the group.
	 * @return int Number of records deleted.
	 */
	public static function delete_all( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE group_id = %d", $group_id ) );
	}

	/**
	 * Delete all group membership information for the specified user.
	 *
	 * In cases where the user is the sole member of a group, a site administrator is
	 * assigned to be the group's administrator. Unhook `groups_remove_data_for_user()`
	 * to modify this behavior.
	 *
	 * @since 1.0.0
	 * @since 4.0.0 The method behavior was changed so that single-member groups are not deleted.
	 *
	 * @param int $user_id ID of the user.
	 * @return bool
	 */
	public static function delete_all_for_user( $user_id ) {
		$group_ids = self::get_group_ids( $user_id );

		foreach ( $group_ids['groups'] as $group_id ) {
			if ( groups_is_user_admin( $user_id, $group_id ) ) {
				// If the user is a sole group admin, install a site admin as their replacement.
				if ( count( groups_get_group_admins( $group_id ) ) < 2 ) {
					$admin = get_users(
						array(
							'blog_id' => bp_get_root_blog_id(),
							'fields'  => 'id',
							'number'  => 1,
							'orderby' => 'ID',
							'role'    => 'administrator',
						)
					);

					if ( ! empty( $admin ) ) {
						groups_join_group( $group_id, $admin[0] );

						$member = new BP_Groups_Member( $admin[0], $group_id );
						$member->promote( 'admin' );
					}
				}
			}

			self::delete( $user_id, $group_id );
		}

		return true;
	}
}
