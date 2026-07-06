# Resumen — OnePieceGaiden.com (Mejorado con Firecrawl)

## Datos generales
- **Tipo:** Foro rol MyBB de temática One Piece con sistema RPG custom avanzado.
- **Comunidad:** Muy activa. ~1660 usuarios activos por semana (miembros + invitados).
- **Sistema de fichas:** PHP/JS custom interactivo. Integra stats mecánicas, biografía y gestión de objetos.

## 1. Patrones de Layout de la Home
- **Estructura Geográfica:** Los foros principales actúan como el "Mundo". Se dividen explícitamente en:
  - Zonas de Juego: SkySea, North Blue, East Blue, South Blue, West Blue, Grand Line (Paradise y New World), Red Line, Calm Belt, Deep Ocean.
  - Zonas Meta: Guías y Enciclopedias, Historia y Cronología, Gestión de Aventuras, Perfiles y Creaciones, Organizaciones y Bandas.
- **Estética Visual:** Uso intensivo de cabeceras de imagen para cada categoría de foro, títulos personalizados (PNG/WEBP) en lugar de texto plano, y banners rotatorios.
- **Sistema de Misiones (Tags):** Los últimos posts revelan un sistema de hilos tagueados por Tiers, por ejemplo: `[T2]`, `[T3]`, `[Conquista T6]`.
- **Sidebar de Datos:** Presenta de manera prominente usuarios en línea, estado del clima/tiempo "VERANO AÑO 725", el censo de usuarios y los últimos posts activos.

## 2. Ficha de Personaje Avanzada (`/op/personaje.php?uid=X`)
El sistema de fichas es extremadamente detallado, funcionando como un hub completo de personaje RPG:

### A. Cabecera (Siempre visible)
- **Metadatos Base:** Raza (con sub-raza, ej. Híbrida), Estatura, Peso, "Espacios", Género, Tipo de Sangre, Edad, Fecha de nacimiento.
- **Facciones y Rangos:** Integración con la Marina (ej. Alférez) y el Inframundo (ej. Operativo), mostrando además el dinero (฿) movido o recompensa.
- **Progresión XP:** Barra de experiencia explícita (XP actual, XP semanal ganada de límite 100, y XP restante para subir de nivel).

### B. Sistema de Pestañas (Portadas, Biografía, Bélico, Técnicas, Inventario)

#### Pestaña 1: PORTADA (Estadísticas y Atributos Principales)
- **Valores Secundarios:** PV (Vitalidad), PE (Energía), PH (Haki).
- **Atributos (FUE, RES, DES, PUN, AGI, REF, VOL):** Cada stat muestra claramente el **Valor Base** y la **Pasiva** por separado para total transparencia mecánica.
- **Reputación:** Eje Positivo vs Negativo.
- **Akuma no Mi:** Indica Nombre, Tipo, Tier, y una sección "Camino de la Akuma" que detalla pasivas mecánicas directas (Ahorro de CD, Optimización de energía, etc.).
- **Rasgos (Virtudes y Defectos):** Poseen un sistema de ID interno (ej. `V001`, `D010`), valores en puntos (equilibrando coste/beneficio) y mecánicas concretas en texto (ej. "+50 de reputación", "-10 a Voluntad cada turno").
- **Economía:** Tres monedas en uso: Nice, Kuro Points, y Berries.

#### Pestaña 2: BIOGRAFÍA
- **Colapsables Narrativos:** Secciones desplegables de Historia, Apariencia, Personalidad y Extras para ahorrar espacio.
- **Oficios (Crafting):** 6 Profesiones (Inventor, Biólogo, Ingeniero, Artesano, Herrero, Modista) con sus niveles actuales.

#### Pestaña 3: BÉLICO (Mecánicas de Combate)
- **Estilos:** Imágenes de estilos equipados (ej. Sora Yokujin, Kanpo Kenpo).
- **Haki:** Tiers de poder definidos.
- **Estadísticas Extendidas:** Valores calculados automáticamente (Defensa Pasiva, Fortaleza Espiritual, Regeneración, Movimiento, Salto, Nado, Trepar, Distancia de Lanzamiento, Límite de caída).
- **Disciplinas:** 12 Clases. Cada clase (ej. Escudero, Artista Marcial) tiene un Rango (E, D, C...) y *Doble Especialización* (ej. Vanguardia y Bastión).

#### Pestañas 4 y 5: TÉCNICAS E INVENTARIO
- **Técnicas:** Categorización por Tiers (T1 a T10).
- **Inventario:** Modal de equipamiento. Slots limitados (29). División de objetos entre armas, consumibles y equipados.

## 3. Conclusiones y Aplicabilidad para I-Forge RPG
Lo que hace a OnePieceGaiden una referencia sobresaliente para un motor de rol en MyBB es su **transparencia y presentación**:
1. **Frontend Desacoplado de la Ficha:** Toda la ficha está pre-cargada en el DOM, permitiendo una navegación fluida por pestañas con JS sin recargar la página de MyBB.
2. **Matemáticas a la vista:** El desglose explícito de (Base + Pasivas) en los stats evita que los usuarios duden de cómo se calcula su poder.
3. **Mapeo Narrativo:** Transformar el índice clásico de foros en un mapa del mundo y etiquetar hilos por nivel `[T3]` crea una inmersión directa en el juego.
4. **Roles y Facetas Múltiples:** Maneja a la perfección variables sociales (Reputación), económicas (3 monedas), narrativas (Rasgos) y de combate (Disciplinas/Stats Extendidas) en un solo dashboard cohesivo.
