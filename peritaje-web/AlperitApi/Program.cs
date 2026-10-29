using AlperitApi.Models;
using Microsoft.EntityFrameworkCore;

var builder = WebApplication.CreateBuilder(args);


// ==============================
// ENTITY FRAMEWORK
// ==============================

var connectionString = builder.Configuration.GetConnectionString("AlperitDB");

if (string.IsNullOrWhiteSpace(connectionString))
{
    throw new InvalidOperationException(
        "Falta configurar la conexión a MySQL."
    );
}

builder.Services.AddDbContext<PeritajeAutosContext>(options =>
    options.UseMySql(
        connectionString,
        ServerVersion.Parse("8.4.3-mysql")
    )
);


// ==============================
// SWAGGER
// ==============================

builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();


// ==============================
// CORS
// ==============================

builder.Services.AddCors(options =>
{
    options.AddPolicy("AlperitFrontend", policy =>
    {
        policy
            .WithOrigins("http://127.0.0.1:5500")
            .AllowAnyHeader()
            .AllowAnyMethod();
    });
});


var app = builder.Build();


// ==============================
// MIDDLEWARE
// ==============================

app.UseCors("AlperitFrontend");


if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}


app.UseHttpsRedirection();


// ==============================
// ENDPOINT DE PRUEBA
// ==============================

app.MapGet("/api/prueba", () =>
{
    return Results.Ok(new
    {
        mensaje = "ALPERIT API funcionando"
    });
});


// ==============================
// CREAR SOLICITUD
// ==============================

app.MapPost(
    "/api/solicitudes",
    async (
        Solicitude solicitud,
        PeritajeAutosContext db
    ) =>
    {

        // ==========================
        // VALIDACIÓN DE AÑOS
        // ==========================

        if (
            solicitud.AnioDesde.HasValue &&
            solicitud.AnioHasta.HasValue &&
            solicitud.AnioHasta < solicitud.AnioDesde
        )
        {
            return Results.BadRequest(new
            {
                mensaje = "El año hasta no puede ser menor que el año desde."
            });
        }


        // Validamos límites razonables
        if (
            solicitud.AnioDesde.HasValue &&
            (solicitud.AnioDesde < 1900 ||
             solicitud.AnioDesde > 2100)
        )
        {
            return Results.BadRequest(new
            {
                mensaje = "El año desde no es válido."
            });
        }


        if (
            solicitud.AnioHasta.HasValue &&
            (solicitud.AnioHasta < 1900 ||
             solicitud.AnioHasta > 2100)
        )
        {
            return Results.BadRequest(new
            {
                mensaje = "El año hasta no es válido."
            });
        }

        // ==========================
// VALIDACIÓN DE MONEDA
// ==========================

if (
    solicitud.Moneda != "ARS" &&
    solicitud.Moneda != "USD"
)
{
    return Results.BadRequest(new
    {
        mensaje = "La moneda seleccionada no es válida."
    });
}


// ==========================
// VALIDACIÓN DE PRESUPUESTO
// ==========================

if (solicitud.PresupuestoMaximo.HasValue)
{
    if (solicitud.PresupuestoMaximo < 0)
    {
        return Results.BadRequest(new
        {
            mensaje = "El presupuesto no puede ser negativo."
        });
    }

    if (
        solicitud.Moneda == "ARS" &&
        solicitud.PresupuestoMaximo > 500000000m
    )
    {
        return Results.BadRequest(new
        {
            mensaje = "El presupuesto máximo permitido en ARS es de $500.000.000."
        });
    }

    if (
        solicitud.Moneda == "USD" &&
        solicitud.PresupuestoMaximo > 500000m
    )
    {
        return Results.BadRequest(new
        {
            mensaje = "El presupuesto máximo permitido en USD es de 500.000."
        });
    }
}
      // ==========================
// BUSCAR O CREAR CLIENTE POR DNI
// ==========================

var cliente = await db.Clientes
    .FirstOrDefaultAsync(c => c.Dni == solicitud.Dni);

if (cliente == null)
{
    cliente = new Cliente
    {
        Nombre = solicitud.Nombre,
        Dni = solicitud.Dni!,
        Email = solicitud.Email,
        Telefono = solicitud.Telefono,
        FechaAlta = DateTime.Now
    };

    db.Clientes.Add(cliente);

    await db.SaveChangesAsync();
}

solicitud.ClienteId = cliente.Id;



        // ==========================
        // DATOS AUTOMÁTICOS
        // ==========================

        solicitud.Estado = "Pendiente";
        solicitud.FechaCreacion = DateTime.Now;


        // El campo viejo queda sin utilizar
        solicitud.AnioMinimo = null;


        // ==========================
        // GUARDAR EN MYSQL
        // ==========================

        db.Solicitudes.Add(solicitud);

        await db.SaveChangesAsync();


        // ==========================
        // RESPUESTA
        // ==========================

        return Results.Ok(new
        {
            mensaje = "Solicitud guardada correctamente",

            id = solicitud.Id,

            estado = solicitud.Estado,

            anioDesde = solicitud.AnioDesde,

            anioHasta = solicitud.AnioHasta
        });
    }
);


// ==============================
// CONTADOR DE PENDIENTES
// ==============================

app.MapGet(
    "/api/solicitudes/pendientes/count",
    async (PeritajeAutosContext db) =>
    {

        var cantidad = await db.Solicitudes
            .CountAsync(
                s => s.Estado == "Pendiente"
            );


        return Results.Ok(new
        {
            pendientes = cantidad
        });
    }
);


app.Run();