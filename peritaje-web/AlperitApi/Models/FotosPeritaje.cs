using System;
using System.Collections.Generic;

namespace AlperitApi.Models;

public partial class FotosPeritaje
{
    public int Id { get; set; }

    public int? PeritajeId { get; set; }

    public string? RutaFoto { get; set; }

    public string? TipoFoto { get; set; }

    public DateTime? FechaCarga { get; set; }

    public virtual Peritaje? Peritaje { get; set; }
}
