using System;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;
using Pomelo.EntityFrameworkCore.MySql.Scaffolding.Internal;

namespace AlperitApi.Models;

public partial class PeritajeAutosContext : DbContext
{
    public PeritajeAutosContext()
    {
    }

    public PeritajeAutosContext(DbContextOptions<PeritajeAutosContext> options)
        : base(options)
    {
    }

    public virtual DbSet<Cliente> Clientes { get; set; }

    public virtual DbSet<FotosPeritaje> FotosPeritajes { get; set; }

    public virtual DbSet<Peritaje> Peritajes { get; set; }

    public virtual DbSet<Solicitude> Solicitudes { get; set; }

    public virtual DbSet<Usuario> Usuarios { get; set; }

    public virtual DbSet<Vehiculo> Vehiculos { get; set; }

    protected override void OnConfiguring(DbContextOptionsBuilder optionsBuilder)
    {
}

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder
            .UseCollation("utf8mb4_0900_ai_ci")
            .HasCharSet("utf8mb4");

        modelBuilder.Entity<Cliente>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PRIMARY");

            entity.ToTable("clientes");

            entity.HasIndex(e => e.Dni, "uk_clientes_dni").IsUnique();

            entity.HasIndex(e => e.Email, "uk_clientes_email").IsUnique();

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Dni)
                .HasMaxLength(20)
                .HasColumnName("dni");
            entity.Property(e => e.Email)
                .HasMaxLength(150)
                .HasColumnName("email");
            entity.Property(e => e.FechaAlta)
                .HasDefaultValueSql("CURRENT_TIMESTAMP")
                .HasColumnType("datetime")
                .HasColumnName("fecha_alta");
            entity.Property(e => e.Nombre)
                .HasMaxLength(150)
                .HasColumnName("nombre");
            entity.Property(e => e.Telefono)
                .HasMaxLength(50)
                .HasColumnName("telefono");
        });

        modelBuilder.Entity<FotosPeritaje>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PRIMARY");

            entity.ToTable("fotos_peritaje");

            entity.HasIndex(e => e.PeritajeId, "fk_fotos_peritaje");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.FechaCarga)
                .HasColumnType("datetime")
                .HasColumnName("fecha_carga");
            entity.Property(e => e.PeritajeId).HasColumnName("peritaje_id");
            entity.Property(e => e.RutaFoto)
                .HasMaxLength(255)
                .HasColumnName("ruta_foto");
            entity.Property(e => e.TipoFoto)
                .HasMaxLength(50)
                .HasColumnName("tipo_foto");

            entity.HasOne(d => d.Peritaje).WithMany(p => p.FotosPeritajes)
                .HasForeignKey(d => d.PeritajeId)
                .HasConstraintName("fk_fotos_peritaje");
        });

        modelBuilder.Entity<Peritaje>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PRIMARY");

            entity.ToTable("peritajes");

            entity.HasIndex(e => e.UsuarioId, "fk_peritajes_usuarios");

            entity.HasIndex(e => e.VehiculoId, "fk_peritajes_vehiculos");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.EstadoGeneral)
                .HasMaxLength(50)
                .HasColumnName("estado_general");
            entity.Property(e => e.FechaPeritaje).HasColumnName("fecha_peritaje");
            entity.Property(e => e.Observaciones)
                .HasColumnType("text")
                .HasColumnName("observaciones");
            entity.Property(e => e.PrecioEstimado)
                .HasPrecision(12, 2)
                .HasColumnName("precio_estimado");
            entity.Property(e => e.UsuarioId).HasColumnName("usuario_id");
            entity.Property(e => e.VehiculoId).HasColumnName("vehiculo_id");

            entity.HasOne(d => d.Usuario).WithMany(p => p.Peritajes)
                .HasForeignKey(d => d.UsuarioId)
                .HasConstraintName("fk_peritajes_usuarios");

            entity.HasOne(d => d.Vehiculo).WithMany(p => p.Peritajes)
                .HasForeignKey(d => d.VehiculoId)
                .HasConstraintName("fk_peritajes_vehiculos");
        });

        modelBuilder.Entity<Solicitude>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PRIMARY");

            entity.ToTable("solicitudes");

            entity.HasIndex(e => e.ClienteId, "fk_solicitudes_clientes");

            entity.HasIndex(e => e.VehiculoSeleccionadoId, "fk_solicitudes_vehiculos");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.AnioDesde).HasColumnName("anio_desde");
            entity.Property(e => e.AnioHasta).HasColumnName("anio_hasta");
            entity.Property(e => e.AnioMinimo).HasColumnName("anio_minimo");
            entity.Property(e => e.ClienteId).HasColumnName("cliente_id");
            entity.Property(e => e.Dni)
                .HasMaxLength(20)
                .HasColumnName("dni");
            entity.Property(e => e.Email)
                .HasMaxLength(150)
                .HasColumnName("email");
            entity.Property(e => e.Estado)
                .HasMaxLength(50)
                .HasDefaultValueSql("'Pendiente'")
                .HasColumnName("estado");
            entity.Property(e => e.FechaCreacion)
                .HasDefaultValueSql("CURRENT_TIMESTAMP")
                .HasColumnType("datetime")
                .HasColumnName("fecha_creacion");
            entity.Property(e => e.FormaCompra)
                .HasMaxLength(50)
                .HasColumnName("forma_compra");
            entity.Property(e => e.KilometrajeMaximo).HasColumnName("kilometraje_maximo");
            entity.Property(e => e.Marca)
                .HasMaxLength(100)
                .HasColumnName("marca");
            entity.Property(e => e.Modelo)
                .HasMaxLength(100)
                .HasColumnName("modelo");
            entity.Property(e => e.Moneda)
                .HasMaxLength(10)
                .HasColumnName("moneda");
            entity.Property(e => e.Nombre)
                .HasMaxLength(150)
                .HasColumnName("nombre");
            entity.Property(e => e.Observaciones)
                .HasColumnType("text")
                .HasColumnName("observaciones");
            entity.Property(e => e.PresupuestoMaximo)
                .HasPrecision(15, 2)
                .HasColumnName("presupuesto_maximo");
            entity.Property(e => e.Telefono)
                .HasMaxLength(50)
                .HasColumnName("telefono");
            entity.Property(e => e.Transmision)
                .HasMaxLength(50)
                .HasColumnName("transmision");
            entity.Property(e => e.VehiculoSeleccionadoId).HasColumnName("vehiculo_seleccionado_id");

            entity.HasOne(d => d.Cliente).WithMany(p => p.Solicitudes)
                .HasForeignKey(d => d.ClienteId)
                .OnDelete(DeleteBehavior.SetNull)
                .HasConstraintName("fk_solicitudes_clientes");

            entity.HasOne(d => d.VehiculoSeleccionado).WithMany(p => p.Solicitudes)
                .HasForeignKey(d => d.VehiculoSeleccionadoId)
                .OnDelete(DeleteBehavior.SetNull)
                .HasConstraintName("fk_solicitudes_vehiculos");
        });

        modelBuilder.Entity<Usuario>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PRIMARY");

            entity.ToTable("usuarios");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Email)
                .HasMaxLength(100)
                .HasColumnName("email");
            entity.Property(e => e.FechaCreacion)
                .HasColumnType("datetime")
                .HasColumnName("fecha_creacion");
            entity.Property(e => e.Nombre)
                .HasMaxLength(100)
                .HasColumnName("nombre");
            entity.Property(e => e.Password)
                .HasMaxLength(255)
                .HasColumnName("password");
            entity.Property(e => e.Rol)
                .HasMaxLength(50)
                .HasColumnName("rol");
        });

        modelBuilder.Entity<Vehiculo>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PRIMARY");

            entity.ToTable("vehiculos");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Anio).HasColumnName("anio");
            entity.Property(e => e.Kilometros).HasColumnName("kilometros");
            entity.Property(e => e.Marca)
                .HasMaxLength(50)
                .HasColumnName("marca");
            entity.Property(e => e.Modelo)
                .HasMaxLength(50)
                .HasColumnName("modelo");
            entity.Property(e => e.Moneda)
                .HasMaxLength(10)
                .HasColumnName("moneda");
            entity.Property(e => e.Patente)
                .HasMaxLength(15)
                .HasColumnName("patente");
            entity.Property(e => e.Precio)
                .HasPrecision(15, 2)
                .HasColumnName("precio");
            entity.Property(e => e.Transmision)
                .HasMaxLength(50)
                .HasColumnName("transmision");
        });

        OnModelCreatingPartial(modelBuilder);
    }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder);
}
