<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class to help set up XProfile fields.
 *
 * @since 1.0.0
 */
#[AllowDynamicProperties]
class BP_XProfile_Field {

	/**
	 * Field ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public int $id = 0;

	/**
	 * Field group ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public int $group_id = 0;

	/**
	 * Field parent ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public int $parent_id = 0;

	/**
	 * Field type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $type = '';

	/**
	 * Field name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $name = '';

	/**
	 * Field description.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $description = '';

	/**
	 * Required field?
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public bool $is_required = false;

	/**
	 * Deletable field?
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public int $can_delete = 1;

	/**
	 * Field position.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public int $field_order = 0;

	/**
	 * Option order.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public int $option_order = 0;

	/**
	 * Order child fields.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $order_by = '';

	/**
	 * Is this the default option?
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public bool $is_default_option = false;

	/**
	 * Field data visibility.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public string $visibility = '';

	/**
	 * Field data visibility.
	 *
	 * @since 1.9.0
	 * @since 2.4.0 Property marked protected. Now accessible by magic method or by `get_default_visibility()`.
	 * @var string
	 */
	protected string $default_visibility = '';

	/**
	 * Is the visibility able to be modified?
	 *
	 * @since 2.3.0
	 * @since 2.4.0 Property marked protected. Now accessible by magic method or by `get_allow_custom_visibility()`.
	 * @var string
	 */
	protected string $allow_custom_visibility = '';

	/**
	 * Whether values from this field are autolinked to directory searches.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	public bool $do_autolink = false;

	/**
	 * The signup position of the field into the signups form.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $signup_position;

	/**
	 * Field type option.
	 *
	 * @since 2.0.0
	 * @var BP_XProfile_Field_Type Field type object used for validation.
	 */
	public $type_obj = null;

	/**
	 * Field data for user ID.
	 *
	 * @since 2.0.0
	 * @var BP_XProfile_ProfileData Field data for user ID.
	 */
	public $data;

	/**
	 * Member types to which the profile field should be applied.
	 *
	 * @since 2.4.0
	 * @var array Array of member types.
	 */
	protected $member_types;

	/**
	 * Initialize and/or populate profile field.
	 *
	 * @since 1.1.0
	 *
	 * @param int|null $id Field ID.
	 * @param int|null $user_id User ID.
	 * @param bool     $get_data Get data.
	 */
	public function __construct( $id = null, $user_id = null, $get_data = true ) {

		if ( ! empty( $id ) ) {
			$this->populate( $id, $user_id, $get_data );

		// Initialize the type obj to prevent fatals when creating new profile fields.
		} else {
			$this->type_obj            = bp_xprofile_create_field_type( 'textbox' );
			$this->type_obj->field_obj = $this;
		}

		/**
		 * Fires when the xProfile field object has been constructed.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_XProfile_Field $field The xProfile field object.
		 */
		do_action( 'bp_xprofile_field', $this );
	}

	/**
	 * Populate a profile field object.
	 *
	 * @since 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 * @global object $userdata
	 *
	 * @param int      $id Field ID.
	 * @param int|null $user_id User ID.
	 * @param bool     $get_data Get data.
	 */
	public function populate( $id, $user_id = null, $get_data = true ) {
		global $wpdb, $userdata;

		if ( empty( $user_id ) ) {
			$user_id = isset( $userdata->ID ) ? $userdata->ID : 0;
		}

		$field = wp_cache_get( $id, 'bp_xprofile_fields' );
		if ( false === $field ) {
			$bp = buddypress();

			$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $id ) );

			if ( ! $field ) {
				return false;
			}

			wp_cache_add( $id, $field, 'bp_xprofile_fields' );
		}

		$this->fill_data( $field );

		if ( ! empty( $get_data ) && ! empty( $user_id ) ) {
			$this->data = $this->get_field_data( $user_id );
		}
	}

	/**
	 * Retrieve a `BP_XProfile_Field` instance.
	 *
	 * @since 2.4.0
	 * @since 2.8.0 Added `$user_id` and `$get_data` parameters.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @static
	 *
	 * @param int      $field_id ID of the field.
	 * @param int|null $user_id  Optional. ID of the user associated with the field.
	 *                           Ignored if `$get_data` is false. If `$get_data` is
	 *                           true, but no `$user_id` is provided, defaults to
	 *                           logged-in user ID.
	 * @param bool     $get_data Whether to fetch data for the specified `$user_id`.
	 * @return BP_XProfile_Field|false Field object if found, otherwise false.
	 */
	public static function get_instance( $field_id, $user_id = null, $get_data = true ) {
		$field_id = (int) $field_id;
		if ( ! $field_id ) {
			return false;
		}

		return new self( $field_id, $user_id, $get_data );
	}

	/**
	 * Fill object vars based on data passed to the method.
	 *
	 * @since 2.4.0
	 *
	 * @param array|object $args Array or object representing the `BP_XProfile_Field` properties.
	 *                           Generally, this is a row from the fields database table.
	 */
	public function fill_data( $args ) {
		if ( is_object( $args ) ) {
			$args = (array) $args;
		}

		$int_fields = array(
			'id', 'is_required', 'group_id', 'parent_id', 'is_default_option',
			'field_order', 'option_order', 'can_delete',
		);

		foreach ( $args as $k => $v ) {
			if ( 'name' === $k || 'description' === $k ) {
				$v = stripslashes( $v );
			}

			// Cast numeric strings as integers.
			if ( true === in_array( $k, $int_fields ) ) {
				$v = (int) $v;
			}

			$this->{$k} = $v;
		}

		// Create the field type and store a reference back to this object.
		$this->type_obj            = bp_xprofile_create_field_type( $this->type );
		$this->type_obj->field_obj = $this;
	}

	/**
	 * Magic getter.
	 *
	 * @since 2.4.0
	 *
	 * @param string $key Property name.
	 * @return string|null
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'default_visibility' :
				return $this->get_default_visibility();
				break;

			case 'allow_custom_visibility' :
				return $this->get_allow_custom_visibility();
				break;
		}
	}

	/**
	 * Magic issetter.
	 *
	 * @since 2.4.0
	 *
	 * @param string $key Property name.
	 * @return bool
	 */
	public function __isset( $key ) {
		switch ( $key ) {
			// Backward compatibility for when these were public methods.
			case 'allow_custom_visibility' :
			case 'default_visibility' :
				return true;
				break;
		}
	}

	/**
	 * Delete a profile field.
	 *
	 * @since 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param boolean $delete_data Whether or not to delete data.
	 * @return bool
	 */
	public function delete( $delete_data = false ) {
		global $wpdb;

		// Prevent deletion if no ID is present.
		// Prevent deletion by url when can_delete is false.
		// Prevent deletion of option 1 since this invalidates fields with options.
		if ( empty( $this->id ) || empty( $this->can_delete ) || ( $this->parent_id && $this->option_order == 1 ) ) {
			return false;
		}

		/**
		 * Fires before the current field instance gets deleted.
		 *
		 * @since 3.0.0
		 *
		 * @param BP_XProfile_Field $field       Current instance of the field being deleted. Passed by reference.
		 * @param bool              $delete_data Whether or not to delete data.
		 */
		do_action_ref_array( 'xprofile_field_before_delete', array( &$this, $delete_data ) );

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE id = %d OR parent_id = %d", $this->id, $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		// Delete all metadata for this field.
		bp_xprofile_delete_meta( $this->id, 'field' );

		// Delete the data in the DB for this field.
		if ( true === $delete_data ) {
			BP_XProfile_ProfileData::delete_for_field( $this->id );
		}

		/**
		 * Fires after the current field instance gets deleted.
		 *
		 * @since 3.0.0
		 *
		 * @param BP_XProfile_Field $field       Current instance of the field being deleted. Passed by reference.
		 * @param bool              $delete_data Whether or not to delete data.
		 */
		do_action_ref_array( 'xprofile_field_after_delete', array( &$this, $delete_data ) );

		return true;
	}

	/**
	 * Save a profile field.
	 *
	 * @since 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->group_id     = apply_filters( 'xprofile_field_group_id_before_save',     $this->group_id,     $this->id );
		$this->parent_id    = apply_filters( 'xprofile_field_parent_id_before_save',    $this->parent_id,    $this->id );
		$this->type         = apply_filters( 'xprofile_field_type_before_save',         $this->type,         $this->id );
		$this->name         = apply_filters( 'xprofile_field_name_before_save',         $this->name,         $this->id );
		$this->description  = apply_filters( 'xprofile_field_description_before_save',  $this->description,  $this->id );
		$this->is_required  = apply_filters( 'xprofile_field_is_required_before_save',  $this->is_required,  $this->id );
		$this->order_by	    = apply_filters( 'xprofile_field_order_by_before_save',     $this->order_by,     $this->id );
		$this->field_order  = apply_filters( 'xprofile_field_field_order_before_save',  $this->field_order,  $this->id );
		$this->option_order = apply_filters( 'xprofile_field_option_order_before_save', $this->option_order, $this->id );
		$this->can_delete   = apply_filters( 'xprofile_field_can_delete_before_save',   $this->can_delete,   $this->id );
		$this->type_obj     = bp_xprofile_create_field_type( $this->type );

		/**
		 * Fires before the current field instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_XProfile_Field $field Current instance of the field being saved.
		 */
		do_action_ref_array( 'xprofile_field_before_save', array( $this ) );

		$is_new_field = is_null( $this->id );

		if ( ! $is_new_field ) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->profile->table_name_fields} SET group_id = %d, parent_id = %d, type = %s, name = %s, description = %s, is_required = %d, order_by = %s, field_order = %d, option_order = %d, can_delete = %d, is_default_option = %d WHERE id = %d",
				$this->group_id,
				$this->parent_id,
				$this->type,
				$this->name,
				$this->description,
				$this->is_required,
				$this->order_by,
				$this->field_order,
				$this->option_order,
				$this->can_delete,
				$this->is_default_option,
				$this->id
			);
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, order_by, field_order, option_order, can_delete, is_default_option ) VALUES ( %d, %d, %s, %s, %s, %d, %s, %d, %d, %d, %d )",
				$this->group_id,
				$this->parent_id,
				$this->type,
				$this->name,
				$this->description,
				$this->is_required,
				$this->order_by,
				$this->field_order,
				$this->option_order,
				$this->can_delete,
				$this->is_default_option
			);
		}

		/**
		 * Check for null so field options can be changed without changing any
		 * other part of the field. The described situation will return 0 here.
		 */
		if ( $wpdb->query( $sql ) !== null ) {

			if ( $is_new_field ) {
				$this->id = $wpdb->insert_id;
			}

			// Only do this if we are editing an existing field.
			if ( ! $is_new_field ) {

				/**
				 * Remove any radio or dropdown options for this
				 * field. They will be re-added if needed.
				 * This stops orphan options if the user changes a
				 * field from a radio button field to a text box.
				 */
				$this->delete_children();
			}

			/**
			 * Check to see if this is a field with child options.
			 * We need to add the options to the db, if it is.
			 */
			if ( $this->type_obj->supports_options ) {

				$parent_id = $this->id;

				// Allow plugins to filter the field's child options (i.e. the items in a selectbox).
				$post_option  = ! empty( $_POST[ "{$this->type}_option" ]           ) ? $_POST[ "{$this->type}_option" ] : '';
				$post_default = ! empty( $_POST[ "isDefault_{$this->type}_option" ] ) ? $_POST[ "isDefault_{$this->type}_option" ] : '';

				/**
				 * Filters the submitted field option value before saved.
				 *
				 * @since 1.5.0
				 *
				 * @param string $post_option Submitted option value.
				 * @param string $type        Current field type being saved for.
				 */
				$options = apply_filters( 'xprofile_field_options_before_save', $post_option, $this->type );

				/**
				 * Filters the default field option value before saved.
				 *
				 * @since 1.5.0
				 *
				 * @param string $post_default Default option value.
				 * @param string $type         Current field type being saved for.
				 */
				$defaults = apply_filters( 'xprofile_field_default_before_save', $post_default, $this->type );

				$counter = 1;
				if ( ! empty( $options ) ) {
					foreach ( (array) $options as $option_key => $option_value ) {
						$is_default = 0;

						if ( is_array( $defaults ) ) {
							if ( isset( $defaults[ $option_key ] ) ) {
								$is_default = 1;
							}
						} else {
							if ( (int) $defaults == $option_key ) {
								$is_default = 1;
							}
						}

						if ( '' != $option_value ) {
							$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, option_order, is_default_option) VALUES (%d, %d, 'option', %s, '', 0, %d, %d)", $this->group_id, $parent_id, $option_value, $counter, $is_default );
							if ( ! $wpdb->query( $sql ) ) {
								return false;
							}
						}

						$counter++;
					}
				}
			}

			/**
			 * Fires after the current field instance gets saved.
			 *
			 * @since 1.0.0
			 *
			 * @param BP_XProfile_Field $field Current instance of the field being saved.
			 */
			do_action_ref_array( 'xprofile_field_after_save', array( $this ) );

			// Recreate type_obj in case someone changed $this->type via a filter.
			$this->type_obj            = bp_xprofile_create_field_type( $this->type );
			$this->type_obj->field_obj = $this;

			return $this->id;
		} else {
			return false;
		}
	}

	/**
	 * Get field data for a user ID.
	 *
	 * @since 1.2.0
	 *
	 * @param int $user_id ID of the user to get field data for.
	 * @return BP_XProfile_ProfileData
	 */
	public function get_field_data( $user_id = 0 ) {
		return new BP_XProfile_ProfileData( $this->id, $user_id );
	}

	/**
	 * Get all child fields for this field ID.
	 *
	 * @since 1.2.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param bool $for_editing Whether or not the field is for editing. Default to false.
	 * @return array
	 */
	public function get_children( $for_editing = false ) {
		global $wpdb;

		// This is done here so we don't have problems with sql injection.
		if ( empty( $for_editing ) && in_array( $this->order_by, array( 'asc', 'desc' ), true ) ) {
			$sort_sql = sprintf( 'ORDER BY name %s', bp_esc_sql_order( $this->order_by ) );
		} else {
			$sort_sql = 'ORDER BY option_order ASC';
		}

		// This eliminates a problem with getting all fields when there is no
		// id for the object.
		if ( empty( $this->id ) ) {
			$parent_id = -1;
		} else {
			$parent_id = $this->id;
		}

		$bp       = buddypress();
		$sql      = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE parent_id = %d AND group_id = %d {$sort_sql}", $parent_id, $this->group_id );
		$children = $wpdb->get_results( $sql );

		/**
		 * Filters the found children for a field.
		 *
		 * @since 1.2.5
		 * @since 3.0.0 Added the `$field_object` parameter.
		 *
		 * @param array             $children     Found children for a field.
		 * @param bool              $for_editing  Whether or not the field is for editing.
		 * @param BP_XProfile_Field $field_object BP_XProfile_Field Field object.
		 */
		return apply_filters( 'bp_xprofile_field_get_children', $children, $for_editing, $this );
	}

	/**
	 * Delete all field children for this field.
	 *
	 * @since 1.2.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function delete_children() {
		global $wpdb;

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE parent_id = %d", $this->id );

		$wpdb->query( $sql );
	}

	/**
	 * Gets the member types to which this field should be available.
	 *
	 * Will not return inactive member types, even if associated metadata is found.
	 *
	 * 'null' is a special pseudo-type, which represents users that do not have a member type.
	 *
	 * @since 2.4.0
	 *
	 * @return array Array of member type names.
	 */
	public function get_member_types() {
		if ( ! is_null( $this->member_types ) ) {
			return $this->member_types;
		}

		$raw_types = bp_xprofile_get_meta( $this->id, 'field', 'member_type', false );

		// If `$raw_types` is not an array, it probably means this is a new field (id=0).
		if ( ! is_array( $raw_types ) ) {
			$raw_types = array();
		}

		// If '_none' is found in the array, it overrides all types.
		$types = array();
		if ( ! in_array( '_none', $raw_types ) ) {
			$registered_types = bp_get_member_types();

			// Eliminate invalid member types saved in the database.
			foreach ( $raw_types as $raw_type ) {
				// 'null' is a special case - it represents users without a type.
				if ( 'null' === $raw_type || isset( $registered_types[ $raw_type ] ) ) {
					$types[] = $raw_type;
				}
			}

			// If no member types have been saved, interpret as *all* member types.
			if ( empty( $types ) ) {
				$types = array_values( $registered_types );

				// + the "null" type, ie users without a type.
				$types[] = 'null';
			}
		}

		/**
		 * Filters the member types to which an XProfile object should be applied.
		 *
		 * @since 2.4.0
		 *
		 * @param array             $types Member types.
		 * @param BP_XProfile_Field $field Field object.
		 */
		$this->member_types = apply_filters( 'bp_xprofile_field_member_types', $types, $this );

		return $this->member_types;
	}

	/**
	 * Sets the member types for this field.
	 *
	 * @since 2.4.0
	 *
	 * @param array $member_types Array of member types. Can include 'null' (users with no type) in addition to any
	 *                            registered types.
	 * @param bool  $append       Whether to append to existing member types. If false, all existing member type
	 *                            associations will be deleted before adding your `$member_types`. Default false.
	 * @return array Member types for the current field, after being saved.
	 */
	public function set_member_types( $member_types, $append = false ) {
		// Unset invalid member types.
		$types = array();
		foreach ( $member_types as $member_type ) {
			// 'null' is a special case - it represents users without a type.
			if ( 'null' === $member_type || bp_get_member_type_object( $member_type ) ) {
				$types[] = $member_type;
			}
		}

		// When `$append` is false, delete all existing types before adding new ones.
		if ( ! $append ) {
			bp_xprofile_delete_meta( $this->id, 'field', 'member_type' );

			/*
			 * We interpret an empty array as disassociating the field from all types. This is
			 * represented internally with the '_none' flag.
			 */
			if ( empty( $types ) ) {
				bp_xprofile_add_meta( $this->id, 'field', 'member_type', '_none' );
			}
		}

		/*
		 * Unrestricted fields are represented in the database as having no 'member_type'.
		 * We detect whether a field is being set to unrestricted by checking whether the
		 * list of types passed to the method is the same as the list of registered types,
		 * plus the 'null' pseudo-type.
		 */
		$_rtypes  = bp_get_member_types();
		$rtypes   = array_values( $_rtypes );
		$rtypes[] = 'null';

		sort( $types );
		sort( $rtypes );

		// Only save if this is a restricted field.
		if ( $types !== $rtypes ) {
			// Save new types.
			foreach ( $types as $type ) {
				bp_xprofile_add_meta( $this->id, 'field', 'member_type', $type );
			}
		}

		// Reset internal cache of member types.
		$this->member_types = null;

		/**
		 * Fires after a field's member types have been updated.
		 *
		 * @since 2.4.0
		 *
		 * @param BP_XProfile_Field $field Current instance of the field.
		 */
		do_action( 'bp_xprofile_field_set_member_type', $this );

		// Refetch fresh items from the database.
		return $this->get_member_types();
	}

	/**
	 * Gets a label representing the field's member types.
	 *
	 * This label is displayed alongside the field's name on the Profile Fields Dashboard panel.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_member_type_label() {
		// Field 1 is always displayed to everyone, so never gets a label.
		if ( 1 == $this->id ) {
			return '';
		}

		// Return an empty string if no member types are registered.
		$all_types = bp_get_member_types();
		if ( empty( $all_types ) ) {
			return '';
		}

		$member_types = $this->get_member_types();

		// If the field applies to all member types, show no message.
		$all_types[] = 'null';
		if ( array_values( $all_types ) == $member_types ) {
			return '';
		}

		$label = '';
		if ( ! empty( $member_types ) ) {
			$has_null = false;
			$member_type_labels = array();
			foreach ( $member_types as $member_type ) {
				if ( 'null' === $member_type ) {
					$has_null = true;
					continue;
				} else {
					$mt_obj = bp_get_member_type_object( $member_type );
					$member_type_labels[] = $mt_obj->labels['name'];
				}
			}

			// Alphabetical sort.
			natcasesort( $member_type_labels );
			$member_type_labels = array_values( $member_type_labels );

			// Add the 'null' option to the end of the list.
			if ( $has_null ) {
				$member_type_labels[] = __( 'Users with no member type', 'buddypress' );
			}

			/* translators: %s: comma separated list of member types */
			$label = sprintf( __( '(Member types: %s)', 'buddypress' ), implode( ', ', array_map( 'esc_html', $member_type_labels ) ) );
		} else {
			$label = '<span class="member-type-none-notice">' . __( '(Unavailable to all members)', 'buddypress' ) . '</span>';
		}

		return $label;
	}

	/**
	 * Get the field's default visibility setting.
	 *
	 * Lazy-loaded to reduce overhead.
	 *
	 * Defaults to 'public' if no visibility setting is found in the database.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_default_visibility() {
		if ( ! isset( $this->default_visibility ) ) {
			$this->default_visibility = 'public';
			$this->visibility         = '';

			if ( isset( $this->type_obj->visibility ) && $this->type_obj->visibility ) {
				$this->visibility = $this->type_obj->visibility;
			}

			if ( $this->field_type_supports( 'allow_custom_visibility' ) ) {
				$this->visibility = bp_xprofile_get_meta( $this->id, 'field', 'default_visibility' );
			}

			if ( $this->visibility ) {
				$this->default_visibility = $this->visibility;
			}
		}

		return $this->default_visibility;
	}

	/**
	 * Get whether the field's default visibility can be overridden by users.
	 *
	 * Lazy-loaded to reduce overhead.
	 *
	 * Defaults to 'allowed'.
	 *
	 * @since 4.4.0
	 *
	 * @return string 'disabled' or 'allowed'.
	 */
	public function get_allow_custom_visibility() {
		if ( ! isset( $this->allow_custom_visibility ) ) {
			$allow_custom_visibility = bp_xprofile_get_meta( $this->id, 'field', 'allow_custom_visibility' );

			if ( 'disabled' === $allow_custom_visibility ) {
				$this->allow_custom_visibility = 'disabled';
			} else {
				$this->allow_custom_visibility = 'allowed';
			}
		}

		return $this->allow_custom_visibility;
	}

	/**
	 * Get the field's signup position.
	 *
	 * @since 8.0.0
	 *
	 * @return int the field's signup position.
	 *             0 if the field has not been added to the signup form.
	 */
	public function get_signup_position() {
		if ( ! isset( $this->signup_position ) ) {
			$this->signup_position = (int) bp_xprofile_get_meta( $this->id, 'field', 'signup_position' );
		}

		return $this->signup_position;
	}

	/**
	 * Get whether the field values should be auto-linked to a directory search.
	 *
	 * Lazy-loaded to reduce overhead.
	 *
	 * Defaults to true for multi and default fields, false for single fields.
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	public function get_do_autolink() {
		if ( ! isset( $this->do_autolink ) ) {
			$do_autolink = bp_xprofile_get_meta( $this->id, 'field', 'do_autolink' );

			if ( '' === $do_autolink ) {
				$this->do_autolink = $this->type_obj->supports_options;
			} else {
				$this->do_autolink = 'on' === $do_autolink;
			}
		}

		/**
		 * Filters the autolink property of the field.
		 *
		 * @since 6.0.0
		 *
		 * @param bool              $do_autolink The autolink property of the field.
		 * @param BP_XProfile_Field $field       Current instance of the field.
		 */
		return apply_filters( 'bp_xprofile_field_do_autolink', $this->do_autolink, $this );
	}

	/* Static Methods ********************************************************/

	/**
	 * Get the type for provided field ID.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $field_id Field ID to get type of.
	 * @return bool|null|string
	 */
	public static function get_type( $field_id = 0 ) {
		global $wpdb;

		// Bail if no field ID.
		if ( empty( $field_id ) ) {
			return false;
		}

		$bp   = buddypress();
		$sql  = $wpdb->prepare( "SELECT type FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id );
		$type = $wpdb->get_var( $sql );

		// Return field type.
		if ( ! empty( $type ) ) {
			return $type;
		}

		return false;
	}

	/**
	 * Delete all fields in a field group.
	 *
	 * @since 1.2.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $group_id ID of the field group to delete fields from.
	 * @return bool
	 */
	public static function delete_for_group( $group_id = 0 ) {
		global $wpdb;

		// Bail if no group ID.
		if ( empty( $group_id ) ) {
			return false;
		}

		$bp      = buddypress();
		$sql     = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id );
		$deleted = $wpdb->get_var( $sql );

		// Return true if fields were deleted.
		if ( false !== $deleted ) {
			return true;
		}

		return false;
	}

	/**
	 * Get field ID from field name.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string $field_name Name of the field to query the ID for.
	 * @return int|null Field ID on success; null on failure.
	 */
	public static function get_id_from_name( $field_name = '' ) {
		global $wpdb;

		$bp = buddypress();

		if ( empty( $bp->profile->table_name_fields ) || empty( $field_name ) ) {
			return false;
		}

		$id = bp_core_get_incremented_cache( $field_name, 'bp_xprofile_fields_by_name' );
		if ( false === $id ) {
			$sql = $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s AND parent_id = 0", $field_name );
			$id = $wpdb->get_var( $sql );
			bp_core_set_incremented_cache( $field_name, 'bp_xprofile_fields_by_name', $id );
		}

		return is_numeric( $id ) ? (int) $id : $id;
	}

	/**
	 * Update field position and/or field group when relocating.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int      $field_id       ID of the field to update.
	 * @param int|null $position       Field position to update.
	 * @param int|null $field_group_id ID of the field group.
	 * @return bool
	 */
	public static function update_position( $field_id, $position = null, $field_group_id = null ) {
		global $wpdb;

		// Bail if invalid position or field group.
		if ( ! is_numeric( $position ) || ! is_numeric( $field_group_id ) ) {
			return false;
		}

		// Get table name and field parent.
		$table_name = buddypress()->profile->table_name_fields;
		$sql        = $wpdb->prepare( "UPDATE {$table_name} SET field_order = %d, group_id = %d WHERE id = %d", $position, $field_group_id, $field_id );
		$parent     = $wpdb->query( $sql );

		$retval = false;

		// Update $field_id with new $position and $field_group_id.
		if ( ! empty( $parent ) && ! is_wp_error( $parent ) ) {

			// Update any children of this $field_id.
			$sql = $wpdb->prepare( "UPDATE {$table_name} SET group_id = %d WHERE parent_id = %d", $field_group_id, $field_id );
			$wpdb->query( $sql );

			// Invalidate profile field and group query cache.
			wp_cache_delete( $field_id, 'bp_xprofile_fields' );

			$retval = $parent;
		}

		bp_core_reset_incrementor( 'bp_xprofile_groups' );

		return $retval;
	}

	/**
	 * Gets the IDs of fields applicable for a given member type or array of member types.
	 *
	 * @since 2.4.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string|array $member_types Member type or array of member types. Use 'any' to return unrestricted
	 *                                   fields (those available for anyone, regardless of member type).
	 * @return array Multi-dimensional array, with field IDs as top-level keys, and arrays of member types
	 *               associated with each field as values.
	 */
	public static function get_fields_for_member_type( $member_types ) {
		global $wpdb;

		$fields = array();

		if ( empty( $member_types ) ) {
			$member_types = array( 'any' );
		} elseif ( ! is_array( $member_types ) ) {
			$member_types = array( $member_types );
		}

		$bp = buddypress();

		// Pull up all recorded field member type data.
		$mt_meta = wp_cache_get( 'field_member_types', 'bp_xprofile' );
		if ( false === $mt_meta ) {
			$mt_meta = $wpdb->get_results( "SELECT object_id, meta_value FROM {$bp->profile->table_name_meta} WHERE meta_key = 'member_type' AND object_type = 'field'" );
			wp_cache_set( 'field_member_types', $mt_meta, 'bp_xprofile' );
		}

		// Keep track of all fields with recorded member_type metadata.
		$all_recorded_field_ids = wp_list_pluck( $mt_meta, 'object_id' );

		// Sort member_type matches in arrays, keyed by field_id.
		foreach ( $mt_meta as $_mt_meta ) {
			if ( ! isset( $fields[ $_mt_meta->object_id ] ) ) {
				$fields[ $_mt_meta->object_id ] = array();
			}

			$fields[ $_mt_meta->object_id ][] = $_mt_meta->meta_value;
		}

		/*
		 * Filter out fields that don't match any passed types, or those marked '_none'.
		 * The 'any' type is implicitly handled here: it will match no types.
		 */
		foreach ( $fields as $field_id => $field_types ) {
			if ( ! array_intersect( $field_types, $member_types ) ) {
				unset( $fields[ $field_id ] );
			}
		}

		// Any fields with no member_type metadata are available to all member types.
		if ( ! in_array( '_none', $member_types ) ) {
			if ( ! empty( $all_recorded_field_ids ) ) {
				$all_recorded_field_ids_sql = implode( ',', array_map( 'absint', $all_recorded_field_ids ) );
				$unrestricted_field_ids = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields} WHERE id NOT IN ({$all_recorded_field_ids_sql})" );
			} else {
				$unrestricted_field_ids = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields}" );
			}

			// Append the 'null' pseudo-type.
			$all_member_types   = bp_get_member_types();
			$all_member_types   = array_values( $all_member_types );
			$all_member_types[] = 'null';

			foreach ( $unrestricted_field_ids as $unrestricted_field_id ) {
				$fields[ $unrestricted_field_id ] = $all_member_types;
			}
		}

		return $fields;
	}

	/**
	 * Validate form field data on submission.
	 *
	 * @since 2.2.0
	 *
	 * @global string $message The feedback message to show.
	 *
	 * @return bool
	 */
	public static function admin_validate() {
		global $message;

		// Check field name.
		if ( ! isset( $_POST['title'] ) || ( '' === $_POST['title'] ) ) {
			$message = esc_html__( 'Profile fields must have a name.', 'buddypress' );
			return false;
		}

		// Check field requirement.
		if ( ! isset( $_POST['required'] ) ) {
			$message = esc_html__( 'Profile field requirement is missing.', 'buddypress' );
			return false;
		}

		// Check field type.
		if ( empty( $_POST['fieldtype'] ) ) {
			$message = esc_html__( 'Profile field type is missing.', 'buddypress' );
			return false;
		}

		// Check that field is of valid type.
		if ( ! in_array( $_POST['fieldtype'], array_keys( bp_xprofile_get_field_types() ), true ) ) {
			/* translators: %s: field type name */
			$message = sprintf( esc_html__( 'The profile field type %s is not registered.', 'buddypress' ), '<code>' . esc_attr( $_POST['fieldtype'] ) . '</code>' );
			return false;
		}

		// Get field type so we can check for and validate any field options.
		$field_type = bp_xprofile_create_field_type( $_POST['fieldtype'] );

		// Field type requires options.
		if ( true === $field_type->supports_options ) {

			// Build the field option key.
			$option_name = sanitize_key( $_POST['fieldtype'] ) . '_option';

			// Check for missing or malformed options.
			if ( empty( $_POST[ $option_name ] ) || ! is_array( $_POST[ $option_name ] ) ) {
				$message = esc_html__( 'These field options are invalid.', 'buddypress' );
				return false;
			}

			// Trim out empty field options.
			$field_values  = array_values( $_POST[ $option_name ] );
			$field_options = array_map( 'sanitize_text_field', $field_values );
			$field_count   = count( $field_options );

			// Check for missing or malformed options.
			if ( 0 === $field_count ) {
				/* translators: %s: field type name */
				$message = sprintf( esc_html__( '%s require at least one option.', 'buddypress' ), $field_type->name );
				return false;
			}

			// If only one option exists, it cannot be an empty string.
			if ( ( 1 === $field_count ) && ( '' === $field_options[0] ) ) {
				/* translators: %s: field type name */
				$message = sprintf( esc_html__( '%s require at least one option.', 'buddypress' ), $field_type->name );
				return false;
			}
		}

		return true;
	}

	/**
	 * Save miscellaneous settings for this field.
	 *
	 * Some field types have type-specific settings, which are saved here.
	 *
	 * @since 2.7.0
	 *
	 * @param array $settings Array of settings.
	 */
	public function admin_save_settings( $settings ) {
		return $this->type_obj->admin_save_settings( $this->id, $settings );
	}

	/**
	 * Populates the items for radio buttons, checkboxes, and dropdown boxes.
	 */
	public function render_admin_form_children() {
		foreach ( array_keys( bp_xprofile_get_field_types() ) as $field_type ) {
			$type_obj = bp_xprofile_create_field_type( $field_type );
			$type_obj->admin_new_field_html( $this );
		}
	}

	/**
	 * Oupput the admin form for this field.
	 *
	 * @since 1.9.0
	 *
	 * @param string $message Message to display.
	 */
	public function render_admin_form( $message = '' ) {

		// Users Admin URL.
		$users_url = bp_get_admin_url( 'users.php' );

		// Add New.
		if ( empty( $this->id ) ) {
			$title  = __( 'Add New Field', 'buddypress' );
			$button	= __( 'Save',          'buddypress' );
			$action = add_query_arg( array(
				'page'     => 'bp-profile-setup',
				'mode'     => 'add_field',
				'group_id' => (int) $this->group_id,
			), $users_url . '#tabs-' . (int) $this->group_id );

			if ( ! empty( $_POST['saveField'] ) ) {
				$this->name        = $_POST['title'];
				$this->description = $_POST['description'];
				$this->is_required = $_POST['required'];
				$this->type        = $_POST['fieldtype'];
				$this->field_order = $_POST['field_order'];

				if ( ! empty( $_POST[ "sort_order_{$this->type}" ] ) ) {
					$this->order_by = $_POST[ "sort_order_{$this->type}" ];
				}
			}

		// Edit.
		} else {
			$title  = __( 'Edit Field', 'buddypress' );
			$button	= __( 'Update',     'buddypress' );
			$action = add_query_arg( array(
				'page'     => 'bp-profile-setup',
				'mode'     => 'edit_field',
				'group_id' => (int) $this->group_id,
				'field_id' => (int) $this->id,
			), $users_url . '#tabs-' . (int) $this->group_id );
		} ?>

		<div class="wrap">

			<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( ! empty( $message ) ) : ?>

				<div id="message" class="error fade notice is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<form id="bp-xprofile-add-field" action="<?php echo esc_url( $action ); ?>" method="post">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-<?php echo ( 1 == get_current_screen()->get_columns() ) ? '1' : '2'; ?>">
						<div id="post-body-content">

							<?php

							// Output the name & description fields.
							$this->name_and_description(); ?>

						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">

							<?php

							// Output the sumbit metabox.
							$this->submit_metabox( $button );

							// Output the required metabox.
							$this->required_metabox();

							// Output signup position metabox.
							$this->signup_position_metabox();

							// Output the Member Types metabox.
							$this->member_type_metabox();

							// Output the field visibility metaboxes.
							$this->visibility_metabox();

							// Output the autolink metabox.
							$this->autolink_metabox();


							/**
							 * Fires after XProfile Field sidebar metabox.
							 *
							 * @since 2.2.0
							 *
							 * @param BP_XProfile_Field $field Current instance of the field.
							 */
							do_action( 'xprofile_field_after_sidebarbox', $this ); ?>

						</div>

						<div id="postbox-container-2" class="postbox-container">

							<?php

							/**
							 * Fires before XProfile Field content metabox.
							 *
							 * @since 2.3.0
							 *
							 * @param BP_XProfile_Field $field Current instance of the field.
							 */
							do_action( 'xprofile_field_before_contentbox', $this );

							// Output the field attributes metabox.
							$this->type_metabox();

							// Output hidden inputs for default field.
							$this->default_field_hidden_inputs();

							/**
							 * Fires after XProfile Field content metabox.
							 *
							 * @since 2.2.0
							 *
							 * @param BP_XProfile_Field $field Current instance of the field.
							 */
							do_action( 'xprofile_field_after_contentbox', $this ); ?>

						</div>
					</div><!-- #post-body -->
				</div><!-- #poststuff -->
			</form>
		</div>

	<?php
	}

	/**
	 * Gets field type supports.
	 *
	 * @since 8.0.0
	 *
	 * @return bool[] Supported features.
	 */
	public function get_field_type_supports() {
		$supports = array(
			'switch_fieldtype'        => true,
			'required'                => true,
			'do_autolink'             => true,
			'allow_custom_visibility' => true,
			'member_types'            => true,
			'signup_position'         => true,
		);

		if ( isset( $this->type_obj ) && $this->type_obj ) {
			$field_type = $this->type_obj;

			if ( isset( $field_type::$supported_features ) ) {
				$supports = array_merge( $supports, $field_type::$supported_features );
			}
		}

		return $supports;
	}

	/**
	 * Checks whether the field type supports the requested feature.
	 *
	 * @since 8.0.0
	 *
	 * @param string $support The name of the feature.
	 * @return bool True if the field type supports the feature. False otherwise.
	 */
	public function field_type_supports( $support = '' ) {
		$retval   = true;
		$features = $this->get_field_type_supports();

		if ( isset( $features[ $support ] ) ) {
			$retval = $features[ $support ];
		}

		return $retval;
	}

	/**
	 * Private method used to display the submit metabox.
	 *
	 * @since 2.3.0
	 *
	 * @param string $button_text Text to put on button.
	 */
	private function submit_metabox( $button_text = '' ) {

		// Setup the URL for deleting
		$users_url  = bp_get_admin_url( 'users.php' );
		$cancel_url = add_query_arg( array(
			'page' => 'bp-profile-setup',
		), $users_url );


		// Delete.
		if ( $this->can_delete ) {
			$delete_url = wp_nonce_url( add_query_arg( array(
				'page'     => 'bp-profile-setup',
				'mode'     => 'delete_field',
				'field_id' => (int) $this->id,
			), $users_url ), 'bp_xprofile_delete_field-' . $this->id, 'bp_xprofile_delete_field' );
		}
		/**
		 * Fires before XProfile Field submit metabox.
		 *
		 * @since 2.1.0
		 *
		 * @param BP_XProfile_Field $field Current instance of the field.
		 */
		do_action( 'xprofile_field_before_submitbox', $this ); ?>

		<div id="submitdiv" class="postbox">
			<h2><?php esc_html_e( 'Submit', 'buddypress' ); ?></h2>
			<div class="inside">
				<div id="submitcomment" class="submitbox">
					<div id="major-publishing-actions">

						<?php

						/**
						 * Fires at the beginning of the XProfile Field publishing actions section.
						 *
						 * @since 2.1.0
						 *
						 * @param BP_XProfile_Field $field Current instance of the field.
						 */
						do_action( 'xprofile_field_submitbox_start', $this ); ?>

						<input type="hidden" name="field_order" id="field_order" value="<?php echo esc_attr( $this->field_order ); ?>" />

						<?php if ( ! empty( $button_text ) ) : ?>

							<div id="publishing-action">
								<input type="submit" name="saveField" value="<?php echo esc_attr( $button_text ); ?>" class="button-primary" />
							</div>

						<?php endif; ?>

						<div id="delete-action">
							<?php if ( ! empty( $this->id ) && isset( $delete_url ) ) : ?>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete deletion"><?php esc_html_e( 'Delete', 'buddypress' ); ?></a>
							<?php endif; ?>

							<div><a href="<?php echo esc_url( $cancel_url ); ?>" class="deletion"><?php esc_html_e( 'Cancel', 'buddypress' ); ?></a></div>
						</div>

						<?php wp_nonce_field( 'xprofile_delete_option' ); ?>

						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>

		<?php

		/**
		 * Fires after XProfile Field submit metabox.
		 *
		 * @since 2.1.0
		 *
		 * @param BP_XProfile_Field $field Current instance of the field.
		 */
		do_action( 'xprofile_field_after_submitbox', $this );
	}

	/**
	 * Private method used to output field name and description fields.
	 *
	 * @since 2.3.0
	 */
	private function name_and_description() {
		// Set default values.
		$description = '';
		$name        = '';

		if ( $this->description ) {
			$description = $this->description;
		}

		if ( $this->name ) {
			$name = $this->name;
		}
	?>

		<div id="titlediv">
			<div class="titlewrap">
				<label id="title-prompt-text" for="title"><?php echo esc_html_x( 'Name (required)', 'XProfile admin edit field', 'buddypress' ); ?></label>
				<input type="text" name="title" id="title" value="<?php echo esc_attr( $name ); ?>" autocomplete="off" />
			</div>
		</div>

		<div class="postbox">
			<h2><?php echo esc_html_x( 'Description', 'XProfile admin edit field', 'buddypress' ); ?></h2>
			<div class="inside">
				<label for="description" class="screen-reader-text">
					<?php
					/* translators: accessibility text */
					esc_html_e( 'Add description', 'buddypress' );
					?>
				</label>
				<textarea name="description" id="description" rows="8" cols="60"><?php echo esc_textarea( $description ); ?></textarea>
			</div>
		</div>

	<?php
	}

	/**
	 * Private method used to output field Member Type metabox.
	 *
	 * @since 2.4.0
	 */
	private function member_type_metabox() {

		// The primary field is for all, so bail.
		if ( true === $this->is_default_field() || ! $this->field_type_supports( 'member_types' ) ) {
			return;
		}

		// Bail when no member types are registered.
		if ( ! $member_types = bp_get_member_types( array(), 'objects' ) ) {
			return;
		}

		$field_member_types = $this->get_member_types();

		?>

		<div id="field-type-member-types" class="postbox">
			<h2><?php esc_html_e( 'Member Types', 'buddypress' ); ?></h2>
			<div class="inside">
				<p class="description"><?php esc_html_e( 'This field should be available to:', 'buddypress' ); ?></p>

				<ul>
					<?php foreach ( $member_types as $member_type ) : ?>
					<li>
						<label for="member-type-<?php echo esc_attr( $member_type->labels['name'] ); ?>">
							<input name="member-types[]" id="member-type-<?php echo esc_attr( $member_type->labels['name'] ); ?>" class="member-type-selector" type="checkbox" value="<?php echo esc_attr( $member_type->name ); ?>" <?php checked( in_array( $member_type->name, $field_member_types ) ); ?>/>
							<?php echo esc_html( $member_type->labels['name'] ); ?>
						</label>
					</li>
					<?php endforeach; ?>

					<li>
						<label for="member-type-none">
							<input name="member-types[]" id="member-type-none" class="member-type-selector" type="checkbox" value="null" <?php checked( in_array( 'null', $field_member_types ) ); ?>/>
							<?php esc_html_e( 'Users with no member type', 'buddypress' ); ?>
						</label>
					</li>

				</ul>
				<p class="description member-type-none-notice<?php if ( ! empty( $field_member_types ) ) : ?> hide<?php endif; ?>"><?php esc_html_e( 'Unavailable to all members.', 'buddypress' ) ?></p>
			</div>

			<input type="hidden" name="has-member-types" value="1" />
		</div>

		<?php
	}

	/**
	 * Private method used to output field visibility metaboxes.
	 *
	 * @since 2.3.0
	 */
	private function visibility_metabox() {

		// Default field and field types not supporting the feature cannot have custom visibility.
		if ( true === $this->is_default_field() || ! $this->field_type_supports( 'allow_custom_visibility' ) ) {
			return;
		} ?>

		<div class="postbox" id="field-type-visibiliy-metabox">
			<h2><label for="default-visibility"><?php esc_html_e( 'Visibility', 'buddypress' ); ?></label></h2>
			<div class="inside">
				<div>
					<select name="default-visibility" id="default-visibility">

						<?php foreach ( bp_xprofile_get_visibility_levels() as $level ) : ?>

							<option value="<?php echo esc_attr( $level['id'] ); ?>" <?php selected( $this->get_default_visibility(), $level['id'] ); ?>>
								<?php echo esc_html( $level['label'] ); ?>
							</option>

						<?php endforeach ?>

					</select>
				</div>

				<div>
					<ul>
						<li>
							<input type="radio" id="allow-custom-visibility-allowed" name="allow-custom-visibility" value="allowed" <?php checked( $this->get_allow_custom_visibility(), 'allowed' ); ?> />
							<label for="allow-custom-visibility-allowed"><?php esc_html_e( 'Allow members to override', 'buddypress' ); ?></label>
						</li>
						<li>
							<input type="radio" id="allow-custom-visibility-disabled" name="allow-custom-visibility" value="disabled" <?php checked( $this->get_allow_custom_visibility(), 'disabled' ); ?> />
							<label for="allow-custom-visibility-disabled"><?php esc_html_e( 'Enforce field visibility', 'buddypress' ); ?></label>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Output the metabox for setting if field is required or not.
	 *
	 * @since 2.3.0
	 */
	private function required_metabox() {

		// Default field and field types not supporting the feature cannot be required.
		if ( true === $this->is_default_field() || ! $this->field_type_supports( 'required' ) ) {
			return;
		} ?>

		<div class="postbox" id="field-type-required-metabox">
			<h2><label for="required"><?php esc_html_e( 'Requirement', 'buddypress' ); ?></label></h2>
			<div class="inside">
				<select name="required" id="required">
					<option value="0"<?php selected( $this->is_required, '0' ); ?>><?php esc_html_e( 'Not Required', 'buddypress' ); ?></option>
					<option value="1"<?php selected( $this->is_required, '1' ); ?>><?php esc_html_e( 'Required',     'buddypress' ); ?></option>
				</select>
			</div>
		</div>

	<?php
	}

	/**
	 * Private method used to output autolink metabox.
	 *
	 * @since 2.5.0
	 */
	private function autolink_metabox() {

		// Field types not supporting the feature cannot use autolink.
		if ( ! $this->field_type_supports( 'do_autolink' ) ) {
			return;
		} ?>

		<div class="postbox" id="field-type-autolink-metabox">
			<h2><?php esc_html_e( 'Autolink', 'buddypress' ); ?></h2>
			<div class="inside">
				<p class="description"><?php esc_html_e( 'On user profiles, link this field to a search of the Members directory, using the field value as a search term.', 'buddypress' ); ?></p>

				<p>
					<label for="do-autolink" class="screen-reader-text"><?php
						/* translators: accessibility text */
						esc_html_e( 'Autolink status for this field', 'buddypress' );
					?></label>
					<select name="do_autolink" id="do-autolink">
						<option value="on" <?php selected( $this->get_do_autolink() ); ?>><?php esc_html_e( 'Enabled', 'buddypress' ); ?></option>
						<option value="" <?php selected( $this->get_do_autolink(), false ); ?>><?php esc_html_e( 'Disabled', 'buddypress' ); ?></option>
					</select>
				</p>
			</div>
		</div>

		<?php
	}

	/**
	 * Output the metabox for setting what type of field this is.
	 *
	 * @since 2.3.0
	 */
	private function type_metabox() {

		// Default field cannot change type.
		if ( true === $this->is_default_field() ) {
			return;
		}
		?>

		<div class="postbox">
			<h2><label for="fieldtype"><?php esc_html_e( 'Type', 'buddypress'); ?></label></h2>
			<div class="inside" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<?php if ( ! $this->field_type_supports( 'switch_fieldtype' ) ) : ?>
					<input type="text" disabled="true" value="<?php echo esc_attr( $this->type_obj->name ); ?>">
					<input type="hidden" name="fieldtype" id="fieldtype" value="<?php echo esc_attr( $this->type ); ?>">

				<?php else : ?>
					<select name="fieldtype" id="fieldtype" onchange="show_options(this.value)">

						<?php bp_xprofile_admin_form_field_types( $this->type ); ?>

					</select>
				<?php endif; ?>

				<?php

				// Deprecated filter, don't use. Go look at {@link BP_XProfile_Field_Type::admin_new_field_html()}.
				do_action( 'xprofile_field_additional_options', $this );

				$this->render_admin_form_children(); ?>

			</div>
		</div>

	<?php
	}

	/**
	 * Output the metabox for setting the field's position into the signup form.
	 *
	 * @since 8.0.0
	 */
	private function signup_position_metabox() {
		// Field types not supporting the feature cannot be added to signups form.
		if ( ! $this->field_type_supports( 'signup_position' ) || true === $this->is_default_field() ) {
			return;
		}

		$next_signup_position = 1;
		$signup_position      = $this->get_signup_position();

		if ( 0 === $signup_position ) {
			$signup_fields_order = bp_xprofile_get_signup_field_ids();
			$next_signup_position = count( $signup_fields_order ) + 1;
		} else {
			$next_signup_position = $signup_position;
		}
		?>

		<div class="postbox" id="field-signup-position-metabox">
			<h2><?php esc_html_e( 'Signups', 'buddypress' ); ?></h2>
			<div class="inside">
				<div>
					<ul>
						<li>
							<input type="checkbox" id="has-signup-position" name="signup-position" value="<?php echo esc_attr( $next_signup_position ); ?>" <?php checked( $signup_position, $next_signup_position ); ?> />
							<label for="has-signup-position"><?php esc_html_e( 'Use this field in the site registration form.', 'buddypress' ); ?></label>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output hidden fields used by default field.
	 *
	 * @since 2.3.0
	 */
	private function default_field_hidden_inputs() {

		// Nonce.
		wp_nonce_field( 'bp_xprofile_admin_field', 'bp_xprofile_admin_field' );

		// Init default field hidden inputs.
		$default_field_hidden_inputs = array();
		$hidden_fields = array(
			'required' => array(
				'name'  => 'required',
				'id'    => 'required',
				'value' => '0',
			),
			'default_visibility' => array(
				'name'  => 'default-visibility',
				'id'    => 'default-visibility',
				'value' => $this->get_default_visibility(),
			),
			'allow_custom_visibility' => array(
				'name'  => 'allow-custom-visibility',
				'id'    => 'allow-custom-visibility',
				'value' => 'disabled',
			),
			'do_autolink' => array(
				'name'  => 'do_autolink',
				'id'    => 'do-autolink',
				'value' => '',
			),
		);

		// Field 1 is the fullname field, which is required.
		if ( true === $this->is_default_field() ) {
			$default_field_required          = $hidden_fields['required'];
			$default_field_required['value'] = '1';

			$default_field_hidden_inputs = array(
				$default_field_required,
				array(
					'name'  => 'fieldtype',
					'id'    => 'fieldtype',
					'value' => 'textbox',
				),
				array(
					'name'  => 'signup-position',
					'id'    => 'has-signup-position',
					'value' => $this->get_signup_position(),
				),
			);
		}

		$supports = $this->get_field_type_supports();
		if ( $supports ) {
			foreach ( $supports as $feature => $support ) {
				if ( true === $support || in_array( $feature, array( 'switch_fieldtype', 'member_types' ), true ) ) {
					continue;
				}

				$default_field_hidden_inputs[] = $hidden_fields[ $feature ];

				if ( 'allow_custom_visibility' === $feature ) {
					$default_field_hidden_inputs[] = $hidden_fields['default_visibility'];
				}
			}
		}

		if ( ! $default_field_hidden_inputs ) {
			return;
		}

		foreach ( $default_field_hidden_inputs as $default_field_hidden_input ) {
			printf(
				'<input type="hidden" name="%1$s" id="%2$s" value="%3$s"/>',
				esc_attr( $default_field_hidden_input['name'] ),
				esc_attr( $default_field_hidden_input['id'] ),
				esc_attr( $default_field_hidden_input['value'] )
			);
		}
	}

	/**
	 * Return if a field ID is the default field.
	 *
	 * @since 2.3.0
	 *
	 * @param int $field_id ID of field to check.
	 * @return bool
	 */
	private function is_default_field( $field_id = 0 ) {

		// Fallback to current field ID if none passed.
		if ( empty( $field_id ) ) {
			$field_id = $this->id;
		}

		// Compare & return.
		return (bool) ( 1 === (int) $field_id );
	}
}
