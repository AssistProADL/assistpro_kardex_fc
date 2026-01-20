#!/bin/bash
# =============================================================================
# Script: sync-db-schema.sh
# Descripción: Sincroniza esquema completo de BD desde DEV hacia otras instancias
#              Incluye: Tablas, Views, Stored Procedures, Functions, Triggers
#              Con múltiples pasadas para resolver dependencias entre views
# =============================================================================

# set -e  # Deshabilitado para permitir continuar ante errores menores

# ===================== CONFIGURACIÓN DE BASES DE DATOS =====================
# Origen (DEV)
SOURCE_HOST="89.117.146.27"
SOURCE_PORT="3306"
SOURCE_DB="assistpro_etl_fc_dev"
SOURCE_USER="root2"
SOURCE_PASS="AdvLogMysql21#"

# Destinos
declare -A TARGETS=(
    ["QA"]="89.117.146.27:3306:assistpro_etl_fc_qa:root2:AdvLogMysql21#"
    ["CANADA"]="89.117.146.27:3306:assistpro_etl_fc_canada:root2:AdvLogMysql21#"
    ["FOAM"]="147.93.138.200:3307:assistpro_etl_fc_prod:advlsystem:AdvLogMysql21#"
)

# Configuración
MAX_PASSES=100          # Máximo de pasadas para views/SPs con dependencias
TEMP_DIR="/tmp/db_sync_$(date +%Y%m%d_%H%M%S)"
LOG_FILE="$TEMP_DIR/sync.log"
AUTO_MODE=false         # Si es true, no pregunta confirmación

mkdir -p "$TEMP_DIR"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# =============================================================================
# FUNCIONES DE LOGGING
# =============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[INFO] $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
    echo "[OK] $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    echo "[WARN] $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE"
}

log_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
    echo "[STEP] $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE"
}

# =============================================================================
# FUNCIONES DE QUERIES
# =============================================================================

run_query() {
    local host=$1
    local port=$2
    local db=$3
    local user=$4
    local pass=$5
    local query=$6
    
    mysql -h "$host" -P "$port" -u "$user" -p"$pass" -N -e "$query" "$db" 2>/dev/null
}

run_query_silent() {
    local host=$1
    local port=$2
    local db=$3
    local user=$4
    local pass=$5
    local query=$6
    
    mysql -h "$host" -P "$port" -u "$user" -p"$pass" -N -e "$query" "$db" 2>&1
}

# =============================================================================
# FUNCIONES DE TABLAS
# =============================================================================

sync_tables() {
    local T_HOST=$1
    local T_PORT=$2
    local T_DB=$3
    local T_USER=$4
    local T_PASS=$5
    local CHANGES_FILE=$6
    
    log_step "Sincronizando TABLAS..."
    
    local source_tables=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
        "SELECT table_name FROM information_schema.tables WHERE table_schema='$SOURCE_DB' AND table_type='BASE TABLE' ORDER BY table_name;")
    
    local target_tables=$(run_query "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" \
        "SELECT table_name FROM information_schema.tables WHERE table_schema='$T_DB' AND table_type='BASE TABLE' ORDER BY table_name;")
    
    local tables_added=0
    local columns_added=0
    local columns_modified=0
    
    # Tablas nuevas
    for table in $source_tables; do
        if ! echo "$target_tables" | grep -q "^${table}$"; then
            log_warning "Tabla nueva: $table"
            
            echo "" >> "$CHANGES_FILE"
            echo "-- Nueva tabla: $table" >> "$CHANGES_FILE"
            
            local create_stmt=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
                "SHOW CREATE TABLE \`$table\`;" | cut -f2)
            
            echo "$create_stmt;" >> "$CHANGES_FILE"
            ((tables_added++))
        fi
    done
    
    # Columnas nuevas/modificadas en tablas existentes
    for table in $source_tables; do
        if echo "$target_tables" | grep -q "^${table}$"; then
            local source_cols=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
                "SELECT column_name, column_type, is_nullable, column_default, extra, column_key 
                 FROM information_schema.columns 
                 WHERE table_schema='$SOURCE_DB' AND table_name='$table' 
                 ORDER BY ordinal_position;")
            
            local target_cols=$(run_query "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" \
                "SELECT column_name, column_type, is_nullable, column_default, extra, column_key 
                 FROM information_schema.columns 
                 WHERE table_schema='$T_DB' AND table_name='$table' 
                 ORDER BY ordinal_position;")
            
            local prev_col=""
            while IFS=$'\t' read -r col_name col_type is_nullable col_default extra col_key; do
                [ -z "$col_name" ] && continue
                
                local target_col=$(echo "$target_cols" | grep "^${col_name}	" || true)
                
                if [ -z "$target_col" ]; then
                    log_warning "Columna nueva: $table.$col_name"
                    
                    local null_clause="NULL"
                    [ "$is_nullable" = "NO" ] && null_clause="NOT NULL"
                    
                    local default_clause=""
                    if [ -n "$col_default" ] && [ "$col_default" != "NULL" ]; then
                        default_clause="DEFAULT '$col_default'"
                    fi
                    
                    local after_clause=""
                    [ -n "$prev_col" ] && after_clause="AFTER \`$prev_col\`"
                    
                    echo "ALTER TABLE \`$table\` ADD COLUMN \`$col_name\` $col_type $null_clause $default_clause $extra $after_clause;" >> "$CHANGES_FILE"
                    ((columns_added++))
                else
                    local target_type=$(echo "$target_col" | cut -f2)
                    if [ "$col_type" != "$target_type" ]; then
                        log_warning "Columna modificada: $table.$col_name ($target_type -> $col_type)"
                        
                        local null_clause="NULL"
                        [ "$is_nullable" = "NO" ] && null_clause="NOT NULL"
                        
                        echo "ALTER TABLE \`$table\` MODIFY COLUMN \`$col_name\` $col_type $null_clause $extra;" >> "$CHANGES_FILE"
                        ((columns_modified++))
                    fi
                fi
                prev_col=$col_name
            done <<< "$source_cols"
        fi
    done
    
    log_success "Tablas: +$tables_added nuevas | Columnas: +$columns_added nuevas, ~$columns_modified modificadas"
}

# =============================================================================
# FUNCIONES DE VIEWS (con múltiples pasadas)
# =============================================================================

sync_views() {
    local T_HOST=$1
    local T_PORT=$2
    local T_DB=$3
    local T_USER=$4
    local T_PASS=$5
    
    log_step "Sincronizando VIEWS (máx $MAX_PASSES pasadas para dependencias)..."
    
    # Obtener todas las views del origen
    local source_views=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
        "SELECT table_name FROM information_schema.views WHERE table_schema='$SOURCE_DB' ORDER BY table_name;")
    
    local total_views=$(echo "$source_views" | wc -w)
    log_info "Total views en origen: $total_views"
    
    # Array para tracking de views pendientes
    declare -A pending_views
    for view in $source_views; do
        pending_views[$view]=1
    done
    
    local pass=1
    local total_created=0
    local total_updated=0
    
    while [ ${#pending_views[@]} -gt 0 ] && [ $pass -le $MAX_PASSES ]; do
        local created_this_pass=0
        local failed_this_pass=0
        local views_to_remove=()
        
        echo -ne "\r${CYAN}[PASS $pass]${NC} Pendientes: ${#pending_views[@]} views...    "
        
        for view in "${!pending_views[@]}"; do
            # Obtener definición de la view
            local view_def=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
                "SHOW CREATE VIEW \`$view\`;" 2>/dev/null | cut -f2)
            
            if [ -z "$view_def" ]; then
                views_to_remove+=("$view")
                continue
            fi
            
            # Limpiar la definición (quitar DEFINER, ajustar nombre de BD)
            view_def=$(echo "$view_def" | sed "s/DEFINER=\`[^\`]*\`@\`[^\`]*\`//g")
            view_def=$(echo "$view_def" | sed "s/\`$SOURCE_DB\`\./\`$T_DB\`./g")
            
            # Crear o reemplazar view
            local create_stmt="CREATE OR REPLACE $view_def"
            
            local result=$(run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "$create_stmt")
            
            if echo "$result" | grep -qi "error"; then
                ((failed_this_pass++))
            else
                views_to_remove+=("$view")
                
                # Verificar si existía antes
                local existed=$(run_query "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" \
                    "SELECT 1 FROM information_schema.views WHERE table_schema='$T_DB' AND table_name='$view' LIMIT 1;" 2>/dev/null)
                
                if [ -n "$existed" ]; then
                    ((total_updated++))
                else
                    ((total_created++))
                fi
                ((created_this_pass++))
            fi
        done
        
        # Remover views exitosas del pending
        for view in "${views_to_remove[@]}"; do
            unset pending_views[$view]
        done
        
        # Si no se creó ninguna view en esta pasada, salir
        if [ $created_this_pass -eq 0 ] && [ ${#pending_views[@]} -gt 0 ]; then
            echo ""
            log_warning "No se pudieron crear ${#pending_views[@]} views después de $pass pasadas"
            log_warning "Views fallidas: ${!pending_views[*]}"
            break
        fi
        
        ((pass++))
    done
    
    echo ""
    log_success "Views: $total_created nuevas, $total_updated actualizadas (en $((pass-1)) pasadas)"
    
    if [ ${#pending_views[@]} -gt 0 ]; then
        echo "Views con errores:" >> "$TEMP_DIR/failed_views.txt"
        for view in "${!pending_views[@]}"; do
            echo "  - $view" >> "$TEMP_DIR/failed_views.txt"
        done
    fi
}

# =============================================================================
# FUNCIONES DE STORED PROCEDURES (con múltiples pasadas)
# =============================================================================

sync_procedures() {
    local T_HOST=$1
    local T_PORT=$2
    local T_DB=$3
    local T_USER=$4
    local T_PASS=$5
    
    log_step "Sincronizando STORED PROCEDURES (máx $MAX_PASSES pasadas)..."
    
    # Obtener todos los procedures del origen
    local source_procs=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
        "SELECT routine_name FROM information_schema.routines 
         WHERE routine_schema='$SOURCE_DB' AND routine_type='PROCEDURE' 
         ORDER BY routine_name;")
    
    local total_procs=$(echo "$source_procs" | wc -w)
    log_info "Total stored procedures en origen: $total_procs"
    
    declare -A pending_procs
    for proc in $source_procs; do
        pending_procs[$proc]=1
    done
    
    local pass=1
    local total_created=0
    local total_updated=0
    
    while [ ${#pending_procs[@]} -gt 0 ] && [ $pass -le $MAX_PASSES ]; do
        local created_this_pass=0
        local procs_to_remove=()
        
        echo -ne "\r${CYAN}[PASS $pass]${NC} Pendientes: ${#pending_procs[@]} procedures...    "
        
        for proc in "${!pending_procs[@]}"; do
            # Obtener definición del procedure
            local proc_def=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
                "SHOW CREATE PROCEDURE \`$proc\`;" 2>/dev/null | cut -f3)
            
            if [ -z "$proc_def" ]; then
                procs_to_remove+=("$proc")
                continue
            fi
            
            # Limpiar DEFINER
            proc_def=$(echo "$proc_def" | sed "s/DEFINER=\`[^\`]*\`@\`[^\`]*\`//g")
            
            # Primero intentar DROP
            run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "DROP PROCEDURE IF EXISTS \`$proc\`;" >/dev/null 2>&1
            
            # Crear procedure
            local result=$(run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "$proc_def")
            
            if echo "$result" | grep -qi "error"; then
                # Puede ser un error de dependencia, intentar en siguiente pasada
                :
            else
                procs_to_remove+=("$proc")
                ((total_created++))
                ((created_this_pass++))
            fi
        done
        
        for proc in "${procs_to_remove[@]}"; do
            unset pending_procs[$proc]
        done
        
        if [ $created_this_pass -eq 0 ] && [ ${#pending_procs[@]} -gt 0 ]; then
            echo ""
            log_warning "No se pudieron crear ${#pending_procs[@]} procedures después de $pass pasadas"
            break
        fi
        
        ((pass++))
    done
    
    echo ""
    log_success "Stored Procedures: $total_created sincronizados (en $((pass-1)) pasadas)"
    
    if [ ${#pending_procs[@]} -gt 0 ]; then
        echo "Procedures con errores:" >> "$TEMP_DIR/failed_procedures.txt"
        for proc in "${!pending_procs[@]}"; do
            echo "  - $proc" >> "$TEMP_DIR/failed_procedures.txt"
        done
    fi
}

# =============================================================================
# FUNCIONES DE FUNCTIONS (con múltiples pasadas)
# =============================================================================

sync_functions() {
    local T_HOST=$1
    local T_PORT=$2
    local T_DB=$3
    local T_USER=$4
    local T_PASS=$5
    
    log_step "Sincronizando FUNCTIONS (máx $MAX_PASSES pasadas)..."
    
    local source_funcs=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
        "SELECT routine_name FROM information_schema.routines 
         WHERE routine_schema='$SOURCE_DB' AND routine_type='FUNCTION' 
         ORDER BY routine_name;")
    
    local total_funcs=$(echo "$source_funcs" | wc -w)
    log_info "Total functions en origen: $total_funcs"
    
    declare -A pending_funcs
    for func in $source_funcs; do
        pending_funcs[$func]=1
    done
    
    local pass=1
    local total_created=0
    
    while [ ${#pending_funcs[@]} -gt 0 ] && [ $pass -le $MAX_PASSES ]; do
        local created_this_pass=0
        local funcs_to_remove=()
        
        echo -ne "\r${CYAN}[PASS $pass]${NC} Pendientes: ${#pending_funcs[@]} functions...    "
        
        for func in "${!pending_funcs[@]}"; do
            local func_def=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
                "SHOW CREATE FUNCTION \`$func\`;" 2>/dev/null | cut -f3)
            
            if [ -z "$func_def" ]; then
                funcs_to_remove+=("$func")
                continue
            fi
            
            func_def=$(echo "$func_def" | sed "s/DEFINER=\`[^\`]*\`@\`[^\`]*\`//g")
            
            run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "DROP FUNCTION IF EXISTS \`$func\`;" >/dev/null 2>&1
            
            local result=$(run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "$func_def")
            
            if ! echo "$result" | grep -qi "error"; then
                funcs_to_remove+=("$func")
                ((total_created++))
                ((created_this_pass++))
            fi
        done
        
        for func in "${funcs_to_remove[@]}"; do
            unset pending_funcs[$func]
        done
        
        if [ $created_this_pass -eq 0 ] && [ ${#pending_funcs[@]} -gt 0 ]; then
            echo ""
            log_warning "No se pudieron crear ${#pending_funcs[@]} functions después de $pass pasadas"
            break
        fi
        
        ((pass++))
    done
    
    echo ""
    log_success "Functions: $total_created sincronizadas (en $((pass-1)) pasadas)"
}

# =============================================================================
# FUNCIONES DE TRIGGERS
# =============================================================================

sync_triggers() {
    local T_HOST=$1
    local T_PORT=$2
    local T_DB=$3
    local T_USER=$4
    local T_PASS=$5
    
    log_step "Sincronizando TRIGGERS..."
    
    local source_triggers=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
        "SELECT trigger_name FROM information_schema.triggers 
         WHERE trigger_schema='$SOURCE_DB' ORDER BY trigger_name;")
    
    local total_triggers=$(echo "$source_triggers" | wc -w)
    log_info "Total triggers en origen: $total_triggers"
    
    local total_created=0
    
    for trigger in $source_triggers; do
        [ -z "$trigger" ] && continue
        
        local trigger_def=$(run_query "$SOURCE_HOST" "$SOURCE_PORT" "$SOURCE_DB" "$SOURCE_USER" "$SOURCE_PASS" \
            "SHOW CREATE TRIGGER \`$trigger\`;" 2>/dev/null | cut -f3)
        
        if [ -z "$trigger_def" ]; then
            continue
        fi
        
        trigger_def=$(echo "$trigger_def" | sed "s/DEFINER=\`[^\`]*\`@\`[^\`]*\`//g")
        
        run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "DROP TRIGGER IF EXISTS \`$trigger\`;" >/dev/null 2>&1
        
        local result=$(run_query_silent "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "$trigger_def")
        
        if ! echo "$result" | grep -qi "error"; then
            ((total_created++))
        fi
    done
    
    log_success "Triggers: $total_created sincronizados"
}

# =============================================================================
# FUNCIÓN DE DUMP COMPLETO (para BDs vacías o nuevas)
# =============================================================================

full_schema_dump() {
    local target_name=$1
    local T_HOST=$2
    local T_PORT=$3
    local T_DB=$4
    local T_USER=$5
    local T_PASS=$6
    
    log_step "BD destino está vacía o es nueva. Realizando DUMP COMPLETO..."
    
    local DUMP_FILE="$TEMP_DIR/full_dump_${target_name}.sql"
    
    # Verificar conectividad primero
    log_info "Verificando conectividad con origen..."
    if ! mysql -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" -p"$SOURCE_PASS" -e "SELECT 1" "$SOURCE_DB" 2>/dev/null; then
        log_error "No se puede conectar a la BD origen: $SOURCE_DB @ $SOURCE_HOST:$SOURCE_PORT"
        return 1
    fi
    
    # 1. Exportar esquema completo desde origen
    log_info "Exportando esquema completo desde $SOURCE_DB..."
    
    # Intentar mysqldump sin suprimir errores para diagnóstico
    mysqldump -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" -p"$SOURCE_PASS" \
        --no-data \
        --routines \
        --triggers \
        --events \
        --single-transaction \
        --skip-add-drop-table \
        "$SOURCE_DB" > "$DUMP_FILE" 2>"$TEMP_DIR/mysqldump_error.log"
    
    local dump_exit_code=$?
    
    if [ $dump_exit_code -ne 0 ] || [ ! -s "$DUMP_FILE" ]; then
        log_error "Error al exportar esquema desde origen (exit code: $dump_exit_code)"
        if [ -f "$TEMP_DIR/mysqldump_error.log" ]; then
            log_error "Detalles del error:"
            cat "$TEMP_DIR/mysqldump_error.log" | head -20
        fi
        return 1
    fi
    
    log_success "Esquema exportado: $(wc -l < "$DUMP_FILE") líneas"
    
    # 2. Limpiar DEFINERs y ajustar nombres de BD
    log_info "Procesando dump para compatibilidad..."
    sed -i "s/DEFINER=\`[^\`]*\`@\`[^\`]*\`//g" "$DUMP_FILE"
    sed -i "s/\`$SOURCE_DB\`\./\`$T_DB\`./g" "$DUMP_FILE"
    
    # 3. Importar a destino
    log_info "Importando esquema completo a $T_DB @ $T_HOST:$T_PORT..."
    
    if [ "$AUTO_MODE" = true ]; then
        mysql -h "$T_HOST" -P "$T_PORT" -u "$T_USER" -p"$T_PASS" "$T_DB" < "$DUMP_FILE" 2>&1 | tee "$TEMP_DIR/import_${target_name}.log"
        
        if [ ${PIPESTATUS[0]} -eq 0 ]; then
            log_success "Dump completo importado exitosamente"
            return 0
        else
            log_warning "Hubo algunos errores durante la importación (ver log)"
            return 0  # Continuar de todos modos
        fi
    else
        echo ""
        echo "--- Vista previa del dump (primeras 50 líneas) ---"
        head -n 50 "$DUMP_FILE"
        echo "..."
        echo "Total de líneas: $(wc -l < "$DUMP_FILE")"
        echo "------------------------------------------------"
        read -p "¿Importar dump completo? (s/n): " apply
        
        if [ "$apply" = "s" ] || [ "$apply" = "S" ]; then
            mysql -h "$T_HOST" -P "$T_PORT" -u "$T_USER" -p"$T_PASS" "$T_DB" < "$DUMP_FILE" 2>&1 | tee "$TEMP_DIR/import_${target_name}.log"
            log_success "Dump completo importado"
            return 0
        else
            log_info "Importación cancelada. Dump guardado en: $DUMP_FILE"
            return 1
        fi
    fi
}

# =============================================================================
# FUNCIÓN PRINCIPAL DE SINCRONIZACIÓN
# =============================================================================

sync_full() {
    local target_name=$1
    local target_config=$2
    
    IFS=':' read -r T_HOST T_PORT T_DB T_USER T_PASS <<< "$target_config"
    
    echo ""
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║  SINCRONIZANDO: $target_name"
    echo "║  Destino: $T_DB @ $T_HOST:$T_PORT"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo ""
    
    local start_time=$(date +%s)
    
    # Detectar si la BD destino está vacía o es nueva
    local table_count=$(run_query "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$T_DB' AND table_type='BASE TABLE';" 2>/dev/null || echo "0")
    
    log_info "Tablas en destino: $table_count"
    
    # Si tiene menos de 5 tablas, consideramos que está vacía y hacemos dump completo
    if [ "$table_count" -lt 5 ]; then
        log_warning "BD destino tiene pocas tablas ($table_count). Modo: DUMP COMPLETO"
        
        if full_schema_dump "$target_name" "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS"; then
            local end_time=$(date +%s)
            local duration=$((end_time - start_time))
            
            echo ""
            echo "╔════════════════════════════════════════════════════════════════╗"
            echo "║  ✅ DUMP COMPLETO EXITOSO: $target_name"
            echo "║  Tiempo: ${duration}s"
            echo "╚════════════════════════════════════════════════════════════════╝"
            return 0
        else
            log_error "Falló el dump completo"
            return 1
        fi
    fi
    
    # Si tiene tablas, hacer sincronización incremental
    log_info "BD destino tiene tablas. Modo: SINCRONIZACIÓN INCREMENTAL"
    
    local CHANGES_FILE="$TEMP_DIR/changes_${target_name}.sql"
    echo "-- Cambios de estructura para $target_name" > "$CHANGES_FILE"
    echo "-- Generado: $(date)" >> "$CHANGES_FILE"
    echo "-- Origen: $SOURCE_DB @ $SOURCE_HOST:$SOURCE_PORT" >> "$CHANGES_FILE"
    echo "" >> "$CHANGES_FILE"
    
    # 1. Sincronizar tablas (genera SQL, no aplica automáticamente)
    sync_tables "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS" "$CHANGES_FILE"
    
    # Aplicar cambios de tablas si hay
    if [ $(wc -l <"$CHANGES_FILE") -gt 5 ]; then
        if [ "$AUTO_MODE" = true ]; then
            log_info "Aplicando cambios de tablas..."
            mysql -h "$T_HOST" -P "$T_PORT" -u "$T_USER" -p"$T_PASS" "$T_DB" < "$CHANGES_FILE" 2>&1 || true
        else
            echo ""
            echo "--- Cambios de tablas pendientes ---"
            tail -n +5 "$CHANGES_FILE"
            echo "------------------------------------"
            read -p "¿Aplicar cambios de tablas? (s/n): " apply
            if [ "$apply" = "s" ] || [ "$apply" = "S" ]; then
                mysql -h "$T_HOST" -P "$T_PORT" -u "$T_USER" -p"$T_PASS" "$T_DB" < "$CHANGES_FILE" 2>&1 || true
                log_success "Cambios de tablas aplicados"
            fi
        fi
    fi
    
    # 2. Sincronizar Views (múltiples pasadas)
    sync_views "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS"
    
    # 3. Sincronizar Functions (antes de procedures por dependencias)
    sync_functions "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS"
    
    # 4. Sincronizar Stored Procedures
    sync_procedures "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS"
    
    # 5. Sincronizar Triggers
    sync_triggers "$T_HOST" "$T_PORT" "$T_DB" "$T_USER" "$T_PASS"
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    echo ""
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║  ✅ SINCRONIZACIÓN COMPLETADA: $target_name"
    echo "║  Tiempo: ${duration}s"
    echo "╚════════════════════════════════════════════════════════════════╝"
}

# =============================================================================
# MENÚ
# =============================================================================

show_menu() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║     SINCRONIZACIÓN DE ESQUEMA DE BASE DE DATOS                ║"
    echo "║     Origen: $SOURCE_DB @ $SOURCE_HOST"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "  1) QA        - $SOURCE_DB → assistpro_etl_fc_qa"
    echo "  2) CANADA    - $SOURCE_DB → assistpro_etl_fc_canada"
    echo "  3) FOAM      - $SOURCE_DB → assistpro_etl_fc_prod"
    echo "  4) TODOS     - Sincronizar a todas las instancias"
    echo "  5) Salir"
    echo ""
    read -p "Opción: " option
    
    case $option in
        1) sync_full "QA" "${TARGETS[QA]}" ;;
        2) sync_full "CANADA" "${TARGETS[CANADA]}" ;;
        3) sync_full "FOAM" "${TARGETS[FOAM]}" ;;
        4)
            for target_name in "${!TARGETS[@]}"; do
                sync_full "$target_name" "${TARGETS[$target_name]}"
            done
            ;;
        5) exit 0 ;;
        *) log_error "Opción inválida"; show_menu ;;
    esac
}

# =============================================================================
# MAIN
# =============================================================================

# Verificar dependencias
for cmd in mysql mysqldump; do
    if ! command -v $cmd &> /dev/null; then
        log_error "$cmd no está instalado"
        exit 1
    fi
done

# Procesar argumentos
while getopts "ay" opt; do
    case $opt in
        a|y) AUTO_MODE=true ;;
    esac
done
shift $((OPTIND-1))

# Ejecutar
if [ -n "$1" ]; then
    case "${1,,}" in
        qa) sync_full "QA" "${TARGETS[QA]}" ;;
        canada) sync_full "CANADA" "${TARGETS[CANADA]}" ;;
        foam) sync_full "FOAM" "${TARGETS[FOAM]}" ;;
        all)
            for target_name in "${!TARGETS[@]}"; do
                sync_full "$target_name" "${TARGETS[$target_name]}"
            done
            ;;
        *) log_error "Target desconocido: $1"; exit 1 ;;
    esac
else
    show_menu
fi

echo ""
log_info "Log guardado en: $LOG_FILE"
log_info "Archivos temporales en: $TEMP_DIR"
