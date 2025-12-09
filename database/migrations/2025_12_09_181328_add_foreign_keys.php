<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Foreign keys are added in a separate migration to ensure all tables exist first.
     */
    public function up(): void
    
        Schema::table('crm_cotizacion_det', function (Blueprint $table) {
                $table->foreign(['cotizacion_id'], 'fk_crm_cot_det_cot')
                      ->references(['id'])
                      ->on('crm_cotizacion')
                      ->onUpdate('CASCADE')
                      ->onDelete('CASCADE');
        });

        Schema::table('c_mto_actividad', function (Blueprint $table) {
                $table->foreign(['familia_id'], 'fk_mto_act_familia')
                      ->references(['id'])
                      ->on('c_mto_familia_servicio');
                $table->foreign(['tipo_id'], 'fk_mto_act_tipo')
                      ->references(['id'])
                      ->on('c_mto_tipo');
        });

        Schema::table('c_secuencia_surtido_det', function (Blueprint $table) {
                $table->foreign(['sec_id'], 'fk_sec_det_sec')
                      ->references(['id'])
                      ->on('c_secuencia_surtido')
                      ->onDelete('CASCADE');
        });

        Schema::table('c_secuencia_surtido_usuario', function (Blueprint $table) {
                $table->foreign(['sec_id'], 'fk_sec_user_sec')
                      ->references(['id'])
                      ->on('c_secuencia_surtido')
                      ->onDelete('CASCADE');
        });

        Schema::table('detallelp', function (Blueprint $table) {
                $table->foreign(['ListaId'], 'fk_detallelp_listap')
                      ->references(['id'])
                      ->on('listap')
                      ->onUpdate('CASCADE')
                      ->onDelete('CASCADE');
        });

        Schema::table('etl_process_docs', function (Blueprint $table) {
                $table->foreign(['process_id'], 'fk_proc_docs_process')
                      ->references(['id'])
                      ->on('etl_processes')
                      ->onDelete('CASCADE');
        });

        Schema::table('etl_process_objects', function (Blueprint $table) {
                $table->foreign(['process_id'], 'fk_proc_obj_process')
                      ->references(['id'])
                      ->on('etl_processes')
                      ->onDelete('CASCADE');
        });

        Schema::table('s_dispositivos', function (Blueprint $table) {
                $table->foreign(['id_almacen'], 's_dispositivos_ibfk_1')
                      ->references(['id'])
                      ->on('c_almacenp');
        });

        Schema::table('s_impresoras', function (Blueprint $table) {
                $table->foreign(['id_almacen'], 'fk_s_impresoras_almacen')
                      ->references(['id'])
                      ->on('c_almacenp');
        });

        Schema::table('td_servicio_caso_log', function (Blueprint $table) {
                $table->foreign(['servicio_id'], 'fk_log_servicio')
                      ->references(['id'])
                      ->on('th_servicio_caso')
                      ->onDelete('CASCADE');
        });

        Schema::table('th_mto_orden', function (Blueprint $table) {
                $table->foreign(['taller_id'], 'fk_mto_orden_taller')
                      ->references(['id'])
                      ->on('c_mto_taller');
                $table->foreign(['tipo_id'], 'fk_mto_orden_tipo')
                      ->references(['id'])
                      ->on('c_mto_tipo');
                $table->foreign(['transporte_id'], 'fk_mto_orden_transporte')
                      ->references(['id'])
                      ->on('t_transporte');
        });

        Schema::table('t_correo_job', function (Blueprint $table) {
                $table->foreign(['plantilla_id'], 'fk_job_plantilla')
                      ->references(['id'])
                      ->on('c_correo_plantilla');
                $table->foreign(['smtp_config_id'], 'fk_job_smtp')
                      ->references(['id'])
                      ->on('c_smtp_config');
        });

        Schema::table('t_crm_actividad', function (Blueprint $table) {
                $table->foreign(['id_lead'], 't_crm_actividad_ibfk_1')
                      ->references(['id_lead'])
                      ->on('t_crm_lead');
                $table->foreign(['id_opp'], 't_crm_actividad_ibfk_2')
                      ->references(['id_opp'])
                      ->on('t_crm_oportunidad');
        });

        Schema::table('t_crm_movimientos_etapa', function (Blueprint $table) {
                $table->foreign(['id_opp'], 't_crm_movimientos_etapa_ibfk_1')
                      ->references(['id_opp'])
                      ->on('t_crm_oportunidad');
        });

        Schema::table('t_crm_oportunidad', function (Blueprint $table) {
                $table->foreign(['id_lead'], 't_crm_oportunidad_ibfk_1')
                      ->references(['id_lead'])
                      ->on('t_crm_lead');
        });

        Schema::table('t_pedido_web_det', function (Blueprint $table) {
                $table->foreign(['pedido_id'], 'fk_pedweb_det_ped')
                      ->references(['id'])
                      ->on('t_pedido_web')
                      ->onDelete('CASCADE');
        });

        Schema::table('t_servicio_foto', function (Blueprint $table) {
                $table->foreign(['servicio_id'], 'fk_foto_servicio')
                      ->references(['id'])
                      ->on('th_servicio_caso')
                      ->onDelete('CASCADE');
        });


    /**
     * Reverse the migrations.
     */
    public function down(): void
    
        Schema::table('crm_cotizacion_det', function (Blueprint $table) {
                $table->dropForeign('fk_crm_cot_det_cot');
        });

        Schema::table('c_mto_actividad', function (Blueprint $table) {
                $table->dropForeign('fk_mto_act_familia');
                $table->dropForeign('fk_mto_act_tipo');
        });

        Schema::table('c_secuencia_surtido_det', function (Blueprint $table) {
                $table->dropForeign('fk_sec_det_sec');
        });

        Schema::table('c_secuencia_surtido_usuario', function (Blueprint $table) {
                $table->dropForeign('fk_sec_user_sec');
        });

        Schema::table('detallelp', function (Blueprint $table) {
                $table->dropForeign('fk_detallelp_listap');
        });

        Schema::table('etl_process_docs', function (Blueprint $table) {
                $table->dropForeign('fk_proc_docs_process');
        });

        Schema::table('etl_process_objects', function (Blueprint $table) {
                $table->dropForeign('fk_proc_obj_process');
        });

        Schema::table('s_dispositivos', function (Blueprint $table) {
                $table->dropForeign('s_dispositivos_ibfk_1');
        });

        Schema::table('s_impresoras', function (Blueprint $table) {
                $table->dropForeign('fk_s_impresoras_almacen');
        });

        Schema::table('td_servicio_caso_log', function (Blueprint $table) {
                $table->dropForeign('fk_log_servicio');
        });

        Schema::table('th_mto_orden', function (Blueprint $table) {
                $table->dropForeign('fk_mto_orden_taller');
                $table->dropForeign('fk_mto_orden_tipo');
                $table->dropForeign('fk_mto_orden_transporte');
        });

        Schema::table('t_correo_job', function (Blueprint $table) {
                $table->dropForeign('fk_job_plantilla');
                $table->dropForeign('fk_job_smtp');
        });

        Schema::table('t_crm_actividad', function (Blueprint $table) {
                $table->dropForeign('t_crm_actividad_ibfk_1');
                $table->dropForeign('t_crm_actividad_ibfk_2');
        });

        Schema::table('t_crm_movimientos_etapa', function (Blueprint $table) {
                $table->dropForeign('t_crm_movimientos_etapa_ibfk_1');
        });

        Schema::table('t_crm_oportunidad', function (Blueprint $table) {
                $table->dropForeign('t_crm_oportunidad_ibfk_1');
        });

        Schema::table('t_pedido_web_det', function (Blueprint $table) {
                $table->dropForeign('fk_pedweb_det_ped');
        });

        Schema::table('t_servicio_foto', function (Blueprint $table) {
                $table->dropForeign('fk_foto_servicio');
        });

};