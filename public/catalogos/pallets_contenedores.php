<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

.ap-container { padding: 20px; font-size: 13px; max-width: 1600px; margin: 0 auto; }
.ap-title { font-size: 20px; font-weight: 600; color: #0b5ed7; margin-bottom: 20px; display: flex; align-items: center;
gap: 10px; }
/* Revert to simple or empty if original file didn't have this, but based on "regresalo a como estaba", I should remove
the big block I added.
However, replace_file_content needs to replace *something*.
I will replace the entire <style>
  block with what I think was there or a minimal version if I don't have the exact original.
 Wait,
  I can just replace the .ap-grid styles back to simple ones if I can't fully delete. 
 Actually,
  the user wants it *back to how it was*. The original file had NO style block for AssistPro (lines 5-380 were added by me in Step 421/428). I should DELETE lines 5-380. replace_file_content can replace with empty string? NO,
  "ReplacementContent" is required string. I will replace with a comment or just " "(space). */ <div class="ap-container"><div class="ap-title"><i class="fa fa-box-open"></i>Pallets / Contenedores</div><div class="ap-cards" id="cards"></div><div class="ap-toolbar"><div class="ap-chip" id="filtroLabel"><i class="fa fa-filter"></i>Almacén: <b>Todos</b></div><div class="ap-search"><i class="fa fa-search"></i><input id="q" placeholder="Buscar clave, tipo, pedido, LP…" onkeydown="if(event.key==='Enter')buscar()"></div><button class="ap-chip" onclick="buscar()">Buscar</button><button class="ap-chip" onclick="limpiar()">Limpiar</button><div style="flex:1"></div><button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i>Agregar</button><button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i>Exportar</button><button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i>Importar</button><button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i>Inactivos</button></div><div class="ap-grid"><table><thead><tr><th>Acciones</th><th>Almacén</th><th>Clave</th><th>Tipo</th><th>Pedido</th><th>Permanente</th><th>LP</th><th>Peso</th><th>Peso Máx</th><th>Cap Vol</th><th>Costo</th><th>Activo</th></tr></thead><tbody id="tb"></tbody></table></div>< !-- Paginación --><div class="ap-pager"><div class="left"><button onclick="prevPage()" id="btnPrev"><i class="fa fa-chevron-left"></i>Anterior</button><button onclick="nextPage()" id="btnNext">Siguiente <i class="fa fa-chevron-right"></i></button><span class="ap-chip" id="lblRange" style="background:transparent; border:none; padding:0;">Mostrando 0–0</span></div><div class="right" style="display:flex; align-items:center;"><span>Página:</span><select id="selPage" onchange="goPage(this.value)"></select><span style="margin-left:15px">Por página:</span><select id="selPerPage" onchange="setPerPage(this.value)"><option value="25" selected>25</option><option value="50">50</option><option value="100">100</option></select></div></div></div>< !-- MODAL CHAROLA --><div class="ap-modal" id="mdl"><div class="ap-modal-content"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px"><h3 style="margin:0"><i class="fa fa-box"></i>Pallet / Contenedor</h3><button onclick="cerrarModal('mdl')"
  style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button></div><div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span>Obligatorios: <b>Almacén</b>,
  <b>Clave</b></div><input type="hidden" id="IDContenedor"><div class="ap-form"><div class="ap-field"><div class="ap-label">Almacén *</div><div class="ap-input"><i class="fa fa-warehouse"></i><input id="cve_almac" oninput="this.value=this.value.replace(/[^0-9]/g,'')"
  placeholder="ID almacén (c_almacenp.id)"></div><div class="ap-error" id="err_alm">Almacén obligatorio.</div></div><div class="ap-field"><div class="ap-label">Clave *</div><div class="ap-input"><i class="fa fa-qrcode"></i><input id="Clave_Contenedor" placeholder="GENEUR36"></div><div class="ap-error" id="err_clv">Clave obligatoria.</div></div><div class="ap-field"><div class="ap-label">Descripción</div><div class="ap-input"><i class="fa fa-align-left"></i><input id="descripcion" placeholder="Descripción"></div></div><div class="ap-field"><div class="ap-label">Tipo</div><div class="ap-input"><i class="fa fa-tag"></i><input id="tipo" placeholder="Pallet"></div></div><div class="ap-field"><div class="ap-label">Pedido</div><div class="ap-input"><i class="fa fa-clipboard"></i><input id="Pedido" placeholder="TR220501496"></div></div><div class="ap-field"><div class="ap-label">Permanente</div><div class="ap-input"><i class="fa fa-infinity"></i><select id="Permanente"><option value="0">No</option><option value="1">Sí</option></select></div></div><div class="ap-field"><div class="ap-label">Sufijo</div><div class="ap-input"><i class="fa fa-hashtag"></i><input id="sufijo"
  oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div></div><div class="ap-field"><div class="ap-label">License Plate</div><div class="ap-input"><i class="fa fa-barcode"></i><input id="CveLP" placeholder="LP00001"></div></div><div class="ap-field"><div class="ap-label">TipoGen</div><div class="ap-input"><i class="fa fa-cogs"></i><input id="TipoGen"
  oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1"></div></div><div class="ap-field"><div class="ap-label">Alto</div><div class="ap-input"><i class="fa fa-ruler-vertical"></i><input id="alto"
  oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="120"></div></div><div class="ap-field"><div class="ap-label">Ancho</div><div class="ap-input"><i class="fa fa-ruler-horizontal"></i><input id="ancho"
  oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1200"></div></div><div class="ap-field"><div class="ap-label">Fondo</div><div class="ap-input"><i class="fa fa-ruler"></i><input id="fondo"
  oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="800"></div></div><div class="ap-field"><div class="ap-label">Peso</div><div class="ap-input"><i class="fa fa-weight"></i><input id="peso" placeholder="2.000"></div></div><div class="ap-field"><div class="ap-label">Peso Máximo</div><div class="ap-input"><i class="fa fa-weight-hanging"></i><input id="pesomax" placeholder="1200.000"></div></div><div class="ap-field"><div class="ap-label">Capacidad Vol</div><div class="ap-input"><i class="fa fa-cube"></i><input id="capavol" placeholder="1.700"></div></div><div class="ap-field"><div class="ap-label">Costo</div><div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="Costo" placeholder="0.000"></div></div><div class="ap-field"><div class="ap-label">Activo</div><div class="ap-input"><i class="fa fa-toggle-on"></i><select id="Activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div></div></div><div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px"><button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button><button class="primary" onclick="guardar()">Guardar</button></div></div></div>< !-- MODAL IMPORT --><div class="ap-modal" id="mdlImport"><div class="ap-modal-content" style="width:700px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px"><h3 style="margin:0"><i class="fa fa-upload"></i>Importar pallets / contenedores</h3><button onclick="cerrarModal('mdlImport')"
  style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button></div><div class="ap-chip" style="margin-bottom:15px">Layout FULL con UPSERT por <b>Clave_Contenedor</b>. Previsualiza antes de importar.</div><div class="ap-input"><i class="fa fa-file-csv"></i><input type="file" id="fileCsv" accept=".csv"></div><div style="margin-top:15px;display:flex;gap:10px"><button class="ghost" onclick="descargarLayout()"><i class="fa fa-download"></i>Descargar layout</button><button class="primary" onclick="previsualizarCsv()"><i class="fa fa-eye"></i>Previsualizar</button></div><div id="csvPreviewWrap" style="display:none;margin-top:15px"><h4 style="margin:0 0 10px; font-size:14px; color:#555;">Previsualización</h4><div class="ap-grid" style="height:200px"><table style="font-size:12px;"><thead id="csvHead"></thead><tbody id="csvBody"></tbody></table></div><div class="ap-chip" id="importMsg" style="margin-top:15px; width:100%; display:none; justify-content:center;"></div></div><div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px"><button class="ghost" onclick="cerrarModal('mdlImport')">Cerrar</button><button class="primary" onclick="importarCsv()" id="btnImportarFinal" style="display:none;">Importar</button></div></div></div><script>const API='../api/pallets_contenedores.php';
  const KPI='../api/pallets_contenedores_kpi.php';

  let filtroAlmClave='';
  let verInactivos=false;
  let qLast='';

  let page=1;
  let perPage=25;
  let total=0;
  let lastRows=[];

  function loadCards() {
    fetch(KPI + '?action=kpi').then(r=> r.json()).then(rows=> {
        let h='';

        rows.forEach(x=> {
            const clave=x.almac_clave || '';
            const nombre=x.almac_nombre || '';

            h +=` <div class="ap-card" onclick="setAlm('${String(clave).replace(/'/g, " \\'")}')">
 <div class="h" > <b><i class="fa fa-warehouse" ></i> $ {
            clave
          }

          </b> <span class="ap-chip ok" >$ {
            x.activas
          }

          Act</span> </div> <div class="k" > <span class="ap-chip" >$ {
            nombre
          }

          </span> <span class="ap-chip" >Total: $ {
            x.total
          }

          </span> <span class="ap-chip warn" >Inac: $ {
            x.inactivas
          }

          </span> <span class="ap-chip" >Perm: $ {
            x.permanentes
          }

          </span> <span class="ap-chip" >Libres: $ {
            x.libres
          }

          </span> <span class="ap-chip" >Con LP: $ {
            x.con_lp
          }

          </span> </div> </div>`;
        });
      cards.innerHTML=h || `<div class="ap-chip warn">Sin datos</div>`;
    });
  }

  function setAlm(clave) {
    filtroAlmClave=clave || '';

    filtroLabel.innerHTML=`<i class="fa fa-filter"></i>Almacén: <b>$ {
      filtroAlmClave ? filtroAlmClave: 'Todos'
    }

    </b>$ {
      filtroAlmClave ? '<span class="ap-chip" style="cursor:pointer" onclick="setAlm(\'\')">Quitar</span>': ''
    }

    `;
    page=1;
    cargar();
  }

  /* ===== Paginación ===== */
  function setPager() {
    const start=total>0 ? ((page - 1) * perPage + (lastRows.length ? 1 : 0)): 0;
    let end=total>0 ? Math.min(page * perPage, total): 0;

    if (total===0) {
      end=0;
    }

    lblRange.innerText=`Mostrando $ {
      start
    }

    –$ {
      end
    }

    `+(total > 0 ? ` de $ {
        total
      }

      ` : '');

    const maxPages=total>0 ? Math.max(1, Math.ceil(total / perPage)) : 1;
    selPage.innerHTML='';

    for (let i=1; i <=maxPages; i++) {
      const o=document.createElement('option');
      o.value=i;
      o.textContent=i;
      if (i===page) o.selected=true;
      selPage.appendChild(o);
    }

    btnPrev.disabled=(page <=1);
    btnNext.disabled=total>0 ? (page >=maxPages) : (lastRows.length < perPage);
  }

  function prevPage() {
    if (page > 1) {
      page--;
      cargar();
    }
  }

  function nextPage() {
    const maxPages=total>0 ? Math.ceil(total / perPage): 1;

    if (page < maxPages) {
      page++;
      cargar();
    }

    else if (total===0 && lastRows.length===perPage) {
      page++;
      cargar();
    }
  }

  function goPage(p) {
    page=Math.max(1, parseInt(p, 10) || 1);
    cargar();
  }

  function setPerPage(v) {
    perPage=parseInt(v, 10) || 25;
    page=1;
    cargar();
  }

  function cargar() {
    const offset=(page - 1) * perPage;
    const url=API+'?action=list'
    +'&almac_clave='+encodeURIComponent(filtroAlmClave)+'&inactivos='+(verInactivos ? 1 : 0)+'&q='+encodeURIComponent(qLast || '')+'&limit='+encodeURIComponent(perPage)+'&offset='+encodeURIComponent(offset);

    fetch(url).then(r=> r.json()).then(resp=> {
        const rows=resp.rows || [];
        total=Number(resp.total || 0) || 0;
        lastRows=rows;

        let h='';

        rows.forEach(c=> {
            const perm=(Number(c.Permanente || 0)===1) ? `<span class="ap-chip ok" >Sí</span>` : `<span class="ap-chip warn" >No</span>`;

            const lp=(c.CveLP && String(c.CveLP).trim() !=='') ? `<span class="ap-chip ok" >$ {
              c.CveLP
            }

            </span>` : `<span class="ap-chip warn" >Libre</span>`;

            h +=` <tr> <td class="ap-actions" > $ {
              verInactivos ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${c.IDContenedor})" ></i>` : `<i class="fa fa-edit" title="Editar" onclick="editar(${c.IDContenedor})" ></i> <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${c.IDContenedor})" ></i>`
            }

            </td> <td>$ {
              c.cve_almac || ''
            }

            </td> <td><b>$ {
              c.Clave_Contenedor || ''
            }

            </b></td> <td>$ {
              c.tipo || ''
            }

            </td> <td>$ {
              c.Pedido || ''
            }

            </td> <td>$ {
              perm
            }

            </td> <td>$ {
              lp
            }

            </td> <td>$ {
              c.peso || ''
            }

            </td> <td>$ {
              c.pesomax || ''
            }

            </td> <td>$ {
              c.capavol || ''
            }

            </td> <td>$ {
              c.Costo || ''
            }

            </td> <td>$ {
              Number(c.Activo || 1)===1 ? '1' : '0'
            }

            </td> </tr>`;
          });
        tb.innerHTML=h || `<tr><td colspan="12" style="text-align:center;color:#6c757d;padding:20px" >Sin resultados</td></tr>`;
        setPager();
      });
  }

  function buscar() {
    qLast=q.value.trim();
    page=1;
    cargar();
  }

  function limpiar() {
    q.value='';
    qLast='';
    page=1;
    cargar();
  }

  function toggleInactivos() {
    verInactivos= !verInactivos;
    page=1;
    cargar();
  }

  function hideErrors() {
    err_alm.style.display='none';
    err_clv.style.display='none';
  }

  function validar() {
    hideErrors();
    let ok=true;

    if ( !cve_almac.value.trim()) {
      err_alm.style.display='block';
      ok=false;
    }

    if ( !Clave_Contenedor.value.trim()) {
      err_clv.style.display='block';
      ok=false;
    }

    return ok;
  }

  function nuevo() {
    document.querySelectorAll('#mdl input').forEach(i=> i.value='');

    document.querySelectorAll('#mdl select').forEach(s=> {
        if (s.id==='Activo') s.value='1';
        else if (s.id==='Permanente') s.value='0';
      });
    IDContenedor.value='';
    hideErrors();
    mdl.style.display='block';
  }

  function editar(id) {
    fetch(API + '?action=get&IDContenedor=' + id).then(r=> r.json()).then(c=> {
        for (let k in c) {
          const el=document.getElementById(k);
          if (el) el.value=(c[k]===null || c[k]===undefined) ? '' : c[k];
        }

        hideErrors();
        mdl.style.display='block';
      });
  }

  function guardar() {
    if ( !validar()) return;

    const fd=new FormData();
    fd.append('action', IDContenedor.value ? 'update' : 'create');
    document.querySelectorAll('#mdl input').forEach(i=> fd.append(i.id, i.value));
    document.querySelectorAll('#mdl select').forEach(s=> fd.append(s.id, s.value));

    fetch(API, {
      method: 'POST', body: fd

    }) .then(r=> r.json()) .then(resp=> {
      if (resp && resp.error) {
        alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
        return;
      }

      cerrarModal('mdl');
      loadCards();
      cargar();
    });
  }

  function eliminar(id) {
    if ( !confirm('¿Inactivar pallet/contenedor?')) return;
    const fd=new FormData();
    fd.append('action', 'delete');
    fd.append('IDContenedor', id);

    fetch(API, {
      method: 'POST', body: fd

    }).then(()=> {
      loadCards(); cargar();
    });
  }

  function recuperar(id) {
    const fd=new FormData();
    fd.append('action', 'restore');
    fd.append('IDContenedor', id);

    fetch(API, {
      method: 'POST', body: fd

    }).then(()=> {
      loadCards(); cargar();
    });
  }

  function exportarDatos() {
    window.open(API + '?action=export_csv&tipo=datos', '_blank');
  }

  function descargarLayout() {
    window.open(API + '?action=export_csv&tipo=layout', '_blank');
  }

  function abrirImport() {
    fileCsv.value='';
    csvPreviewWrap.style.display='none';
    importMsg.style.display='none';
    document.getElementById('btnImportarFinal').style.display='none';
    mdlImport.style.display='block';
  }

  function previsualizarCsv() {
    const f=fileCsv.files[0];

    if ( !f) {
      alert('Selecciona un CSV');
      return;
    }

    const r=new FileReader();

    r.onload=e=> {
      const rows=e.target.result.split('\n').filter(x=> x.trim() !=='');

      csvHead.innerHTML='<tr>'+rows[0].split(',').map(h=> `<th>$ {
          h
        }

        </th>`).join('')+'</tr>';

      csvBody.innerHTML=rows.slice(1, 6).map(r=> '<tr>' + r.split(',').map(c=> `<td>$ {
            c
          }

          </td>`).join('') + '</tr>').join('');
      csvPreviewWrap.style.display='block';
      importMsg.style.display='none';
      document.getElementById('btnImportarFinal').style.display='block';
    }

    ;
    r.readAsText(f);
  }

  function importarCsv() {
    const fd=new FormData();
    fd.append('action', 'import_csv');
    fd.append('file', fileCsv.files[0]);

    fetch(API, {
      method: 'POST', body: fd

    }) .then(r=> r.json()) .then(resp=> {
      importMsg.style.display='flex';

      if (resp.error) {
        importMsg.className='ap-chip warn';

        importMsg.innerHTML=`<b>Error:</b> $ {
          resp.error
        }

        `;
        return;
      }

      importMsg.className='ap-chip ok';

      importMsg.innerHTML=`<b>Importación:</b> OK $ {
        resp.rows_ok || 0
      }

      | Err $ {
        resp.rows_err || 0
      }

      `;

      setTimeout(()=> {
          cerrarModal('mdlImport'); loadCards(); cargar();
        }

        , 2000);
    });
  }

  function cerrarModal(id) {
    document.getElementById(id).style.display='none';
  }

  document.addEventListener('DOMContentLoaded', ()=> {
      selPerPage.value='25';
      loadCards();
      cargar();
    });
  </script><?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>