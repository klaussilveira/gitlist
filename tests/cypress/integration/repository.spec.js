describe('Repository page', () => {
  it('successfully loads', () => {
    cy.visit('/git-bare-repo');

    cy.get('.card-body > .nav > :nth-child(1) > .nav-link').should('have.text', ' 2 Branches');
    cy.get('.card-body > .nav > :nth-child(2) > .nav-link').should('have.text', ' 1 Tags');
    cy.get('.card-header > :nth-child(1)').should('have.text', '\n            \n            Klaus Silveira\n            Fixed mm.\n          ');
    cy.get('.float-right').should('have.text', '\n            a003d30 @ 2016-11-24 12:30:04\n          ');
  });

  it('shows branch dropdown', () => {
    cy.visit('/git-bare-repo');

    cy.get('.dropdown > .btn').click();
    cy.get('[href="/git-bare-repo/tree/feature/1.2-dev/"]').should('have.text', 'feature/1.2-dev');
  });

  it('shows reflist dropdown', () => {
    cy.visit('/git-bare-repo');

    cy.get('.dropdown > .btn').click();
    cy.get('[href="/git-bare-repo/tree/feature/1.2-dev/"]').should('have.text', 'feature/1.2-dev');
    cy.get('#tags-tab').click();
    cy.get('#tags > .list-group > .list-group-item').should('have.text', '1.2');
  });

  it('shows reflist dropdown and autocompletes', () => {
    cy.visit('/git-bare-repo');

    cy.get('.dropdown > .btn').click();
    cy.get('.dropdown-menu > .input-group > .form-control').clear();
    cy.get('.dropdown-menu > .input-group > .form-control').type('feature/1.2-dev');
    cy.get('h1').should('be.visible');
    cy.url().should('be.equal', 'http://0.0.0.0:8880/git-bare-repo/tree/feature/1.2-dev/')
  });

  it('swaps clone url', () => {
    cy.visit('/git-bare-repo');

    cy.get('.input-group-prepend > .btn').click();
    cy.get('[data-clone-url="https://gitlist.org/git-bare-repo.git"]').click();
    cy.get('.btn-toolbar > .input-group > .form-control').should('have.value', 'https://gitlist.org/git-bare-repo.git');
    cy.get('.input-group-prepend > .btn').click();
    cy.get('[data-clone-url="git@gitlist.org:git-bare-repo.git"]').click();
    cy.get('.btn-toolbar > .input-group > .form-control').should('have.value', 'git@gitlist.org:git-bare-repo.git');
  });
});
