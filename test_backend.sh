create_backend_files() {
    print_status "Creating essential backend files..."
    
    # Create directories
    mkdir -p "$APP_DIR/src/database" "$APP_DIR/src/controllers" "$APP_DIR/src/middleware" \
        "$APP_DIR/src/routes" "$APP_DIR/src/socket" "$APP_DIR/src/utils"
    
    # Create modular middleware files
    create_middleware_files()
    
    # Create modular controller files
    create_controller_files()
    
    # Create modular route files
    create_route_files()
